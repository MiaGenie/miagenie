<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ImageRevisionMode;
use App\Enums\ModelTier;
use App\Genie\Images\ImageCallLog;
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
use Laravel\Ai\Responses\Data\ImageUsage;
use Laravel\Ai\Responses\Data\Meta;
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
                $row->setRelation('file', new WorkspaceFile(['uuid' => 'revised-77']));

                return $row;
            }
        };
    }

    /**
     * A call log that records to an array instead of the database.
     */
    protected function callLog(): ImageCallLog
    {
        return new class extends ImageCallLog
        {
            public array $records = [];

            public function record(AiRunStep $runStep, ImagePrompt $prompt, ?AiRunStepFile $file, int $duration, ?string $error = null, ?string $mode = null, ?AiRunStepFile $parent = null): void
            {
                $this->records[] = [
                    'run_step' => $runStep->id,
                    'prompt' => $prompt->text(),
                    'file' => $file?->file?->uuid,
                    'error' => $error,
                    'mode' => $mode,
                    'parent' => $parent?->id,
                ];
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

    protected function revision(ImageStore $store, ?ImageCallLog $log = null): ImageRevision
    {
        $agents = $this->createMock(StepAgentFactory::class);
        $agents->method('prepare')->willReturn(Lab::OpenAI);
        $agents->method('timeout')->willReturn(60);
        $agents->method('imageModel')->willReturn(null);

        return new ImageRevision(new ImageGenerator($agents), $store, $log ?? $this->callLog());
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

    public function test_a_revision_is_one_logged_call_with_its_mode_and_parent(): void
    {
        Image::fake();

        $log = $this->callLog();

        $this->revision($this->store(), $log)->revise($this->image(), new AiRun, ImageRevisionMode::FIX, new ImageContext('a red chair', 'bluer', 2), 'en-GB');

        $this->assertCount(1, $log->records);
        $this->assertSame(5, $log->records[0]['run_step']);
        $this->assertSame('revised-77', $log->records[0]['file']);
        $this->assertSame('fix', $log->records[0]['mode']);
        $this->assertSame(40, $log->records[0]['parent']);
        $this->assertNull($log->records[0]['error']);
    }

    public function test_the_call_is_logged_on_the_step_it_is_told_to_while_the_file_stays_on_the_slot_step(): void
    {
        Image::fake();

        $store = $this->store();
        $log = $this->callLog();

        $revisionStep = new AiRunStep(['step_id' => 55]);
        $revisionStep->id = 9;

        $this->revision($store, $log)->revise($this->image(), new AiRun, ImageRevisionMode::FIX, new ImageContext('a red chair', 'bluer', 2), 'en-GB', new ImageOptions, null, $revisionStep);

        $this->assertSame(9, $log->records[0]['run_step']);
        $this->assertSame(40, $store->persisted['parent']);
        $this->assertSame(2, $store->persisted['position']);
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
        Image::fake([new ImageResponse(new Collection, new ImageUsage, new Meta('openai', 'gpt-image-2'))]);

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

    public function test_a_failed_revision_is_logged_with_its_error(): void
    {
        Image::fake([new ImageResponse(new Collection, new ImageUsage, new Meta('openai', 'gpt-image-2'))]);

        $log = $this->callLog();

        try {
            $this->revision($this->store(), $log)->revise($this->image(), new AiRun, ImageRevisionMode::FIX, new ImageContext('a red chair', 'bluer', 2), 'en-GB');
        } catch (RuntimeException) {
        }

        $this->assertCount(1, $log->records);
        $this->assertSame('fix', $log->records[0]['mode']);
        $this->assertStringContainsString('returned no image', $log->records[0]['error']);
    }
}
