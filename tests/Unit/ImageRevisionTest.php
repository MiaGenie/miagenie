<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ImageRevisionMode;
use App\Enums\ModelTier;
use App\Genie\Images\ImageContext;
use App\Genie\Images\ImageGenerator;
use App\Genie\Images\ImageOptions;
use App\Genie\Images\ImagePrompt;
use App\Genie\Images\ImageRevision;
use App\Genie\Images\ImageStore;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\AiRunStepFile;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use App\Models\WorkspaceFile;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\StoredImage;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt as SdkImagePrompt;
use Laravel\Ai\Responses\Data\GeneratedImage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\ImageResponse;
use RuntimeException;
use Tests\TestCase;

class ImageRevisionTest extends TestCase
{
    /**
     * A store that records instead of writing.
     */
    protected function store(): ImageStore
    {
        return new class extends ImageStore
        {
            public array $revising = [];

            public array $failed = [];

            public ?array $persisted = null;

            public function markRevising(AiRunStepFile $image): void
            {
                $this->revising[] = $image->id;
            }

            public function markRevisionFailed(AiRunStepFile $image, string $error): void
            {
                $this->failed[] = [$image->id, $error];
            }

            public function persist(AiRunStep $runStep, AiRun $run, int $position, ImagePrompt $prompt, GeneratedImage $image, ?AiRunStepFile $parent = null, ?string $mode = null): AiRunStepFile
            {
                $this->persisted = [
                    'position' => $position,
                    'prompt' => $prompt->text(),
                    'brief' => $prompt->context()->brief,
                    'feedback' => $prompt->context()->feedback,
                    'reference' => $prompt->reference()?->path,
                    'parent' => $parent?->id,
                    'mode' => $mode,
                ];

                $row = new AiRunStepFile(['position' => $position, 'workspace_file_id' => 200, 'mode' => $mode]);
                $row->id = 77;

                return $row;
            }
        };
    }

    protected function image(): AiRunStepFile
    {
        $step = new RuleStep(['name' => 'Slides', 'message' => ['en-GB' => 'Slide {{{position}}}: {{{brief}}} {{{feedback}}}']]);
        $step->setRelation('modelProfile', new ModelProfile(['provider' => 'openai', 'model_tier' => ModelTier::DEFAULT]));

        $runStep = new AiRunStep(['step_id' => 7]);
        $runStep->id = 5;
        $runStep->setRelation('step', $step);

        $image = new AiRunStepFile(['position' => 2, 'workspace_file_id' => 100, 'brief' => 'a red chair']);
        $image->id = 40;
        $image->setRelation('runStep', $runStep);
        $image->setRelation('file', new WorkspaceFile(['path' => 'workspace/w/generated/old.png', 'disk' => 'public']));

        return $image;
    }

    protected function revision(ImageStore $store): ImageRevision
    {
        $agents = $this->createMock(StepAgentFactory::class);
        $agents->method('prepare')->willReturn(Lab::OpenAI);
        $agents->method('timeout')->willReturn(60);
        $agents->method('imageModel')->willReturn(null);

        return new ImageRevision(new ImageGenerator($agents), $store);
    }

    public function test_fix_attaches_the_current_file_keeps_the_brief_and_persists_as_a_fix(): void
    {
        Image::fake();

        $store = $this->store();
        $image = $this->image();

        $result = $this->revision($store)->revise($image, new AiRun, ImageRevisionMode::FIX, new ImageContext('a red chair', 'make it blue', 2), 'en-GB', new ImageOptions);

        $this->assertSame([40], $store->revising);
        $this->assertSame([], $store->failed);
        $this->assertSame(2, $store->persisted['position']);
        $this->assertSame('Slide 2: a red chair make it blue', $store->persisted['prompt']);
        $this->assertSame('a red chair', $store->persisted['brief']);
        $this->assertSame('make it blue', $store->persisted['feedback']);
        $this->assertSame('workspace/w/generated/old.png', $store->persisted['reference']);
        $this->assertSame(40, $store->persisted['parent']);
        $this->assertSame('fix', $store->persisted['mode']);
        $this->assertSame(77, $result->id);

        Image::assertGenerated(function (SdkImagePrompt $prompt) {
            $attachment = $prompt->attachments->first();

            return $attachment instanceof StoredImage && $attachment->path === 'workspace/w/generated/old.png';
        });
    }

    public function test_a_template_step_supplies_the_message_and_the_profile_while_the_slot_keeps_its_run_step(): void
    {
        Image::fake();

        $store = $this->store();

        $template = new RuleStep(['name' => 'Fix', 'message' => ['en-GB' => 'Fix {{{brief}}}: {{{feedback}}}']]);
        $template->setRelation('modelProfile', new ModelProfile(['provider' => 'openai', 'model_tier' => ModelTier::OTHER, 'model' => 'gpt-image-1']));

        $this->revision($store)->revise($this->image(), new AiRun, ImageRevisionMode::FIX, new ImageContext('a red chair', 'bluer', 2), 'en-GB', new ImageOptions, $template);

        $this->assertSame('Fix a red chair: bluer', $store->persisted['prompt']);
        $this->assertSame(2, $store->persisted['position']);
        $this->assertSame(40, $store->persisted['parent']);
    }

    public function test_rethink_attaches_nothing_and_uses_the_submitted_brief(): void
    {
        Image::fake();

        $store = $this->store();

        $this->revision($store)->revise($this->image(), new AiRun, ImageRevisionMode::RETHINK, new ImageContext('a green sofa', null, 2), 'en-GB');

        $this->assertSame('Slide 2: a green sofa ', $store->persisted['prompt']);
        $this->assertSame('a green sofa', $store->persisted['brief']);
        $this->assertNull($store->persisted['feedback']);
        $this->assertNull($store->persisted['reference']);
        $this->assertSame('rethink', $store->persisted['mode']);

        Image::assertGenerated(fn (SdkImagePrompt $prompt) => $prompt->attachments->isEmpty() && ! $prompt->contains('Keep everything else'));
    }

    public function test_an_unchanged_brief_reproduces_the_original_prompt(): void
    {
        Image::fake();

        $store = $this->store();

        $this->revision($store)->revise($this->image(), new AiRun, ImageRevisionMode::RETHINK, new ImageContext('a red chair', null, 2), 'en-GB');

        $this->assertSame('Slide 2: a red chair ', $store->persisted['prompt']);
    }

    public function test_a_fix_without_feedback_and_a_rethink_without_a_brief_are_refused_before_any_call(): void
    {
        Image::fake();

        $store = $this->store();
        $revision = $this->revision($store);

        try {
            $revision->revise($this->image(), new AiRun, ImageRevisionMode::FIX, new ImageContext('a red chair', null, 2), 'en-GB');
            $this->fail('A fix without feedback was accepted.');
        } catch (InvalidArgumentException) {
        }

        try {
            $revision->revise($this->image(), new AiRun, ImageRevisionMode::RETHINK, new ImageContext(null, null, 2), 'en-GB');
            $this->fail('A rethink without a brief was accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame([], $store->revising);
        Image::assertNothingGenerated();
    }

    public function test_a_failed_generation_records_the_error_on_the_slot_and_rethrows(): void
    {
        Image::fake([new ImageResponse(new Collection, new Usage, new Meta('openai', 'gpt-image-2'))]);

        $store = $this->store();

        try {
            $this->revision($store)->revise($this->image(), new AiRun, ImageRevisionMode::FIX, new ImageContext('a red chair', 'bluer', 2), 'en-GB');
            $this->fail('The empty response was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('returned no image', $exception->getMessage());
        }

        $this->assertSame([40], $store->revising);
        $this->assertCount(1, $store->failed);
        $this->assertSame(40, $store->failed[0][0]);
        $this->assertNull($store->persisted);
    }
}
