<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ModelTier;
use App\Genie\Images\ImageContext;
use App\Genie\Images\ImageGenerator;
use App\Genie\Images\ImageOptions;
use App\Genie\Images\ImagePrompt;
use App\Genie\Images\ImageStep;
use App\Genie\Images\ImageStore;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\AiRunStepFile;
use App\Models\ModelProfile;
use App\Models\RuleStep;
use App\Models\WorkspaceFile;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt as SdkImagePrompt;
use Laravel\Ai\Responses\Data\GeneratedImage;
use Tests\TestCase;

class ImageStepTest extends TestCase
{
    /**
     * An in-memory store: slots are an array, nothing is written anywhere.
     */
    protected function store(array $existing = []): ImageStore
    {
        return new class($existing) extends ImageStore
        {
            public array $persisted = [];

            public function __construct(public array $slots) {}

            public function current(AiRunStep $runStep, int $position): ?AiRunStepFile
            {
                return $this->slots[$position] ?? null;
            }

            public function persist(AiRunStep $runStep, AiRun $run, int $position, ImagePrompt $prompt, GeneratedImage $image, ?AiRunStepFile $parent = null, ?string $mode = null): AiRunStepFile
            {
                $this->persisted[] = ['position' => $position, 'prompt' => $prompt->text(), 'brief' => $prompt->context()->brief];

                $row = new AiRunStepFile(['position' => $position, 'workspace_file_id' => 100 + $position]);
                $row->setRelation('file', new WorkspaceFile(['uuid' => "fresh-{$position}"]));

                return $this->slots[$position] = $row;
            }
        };
    }

    protected function runStep(): AiRunStep
    {
        $step = new RuleStep(['name' => 'Slides', 'message' => ['en-GB' => 'Slide {{{position}}}: {{{brief}}}']]);
        $step->setRelation('modelProfile', new ModelProfile(['provider' => 'openai', 'model_tier' => ModelTier::DEFAULT]));

        $runStep = new AiRunStep(['step_id' => 7]);
        $runStep->setRelation('step', $step);

        return $runStep;
    }

    protected function imageStep(ImageStore $store): ImageStep
    {
        $agents = $this->createMock(StepAgentFactory::class);
        $agents->method('prepare')->willReturn(Lab::OpenAI);
        $agents->method('timeout')->willReturn(60);
        $agents->method('imageModel')->willReturn(null);

        return new ImageStep(new ImageGenerator($agents), $store);
    }

    public function test_one_image_per_brief_in_slot_order(): void
    {
        Image::fake();

        $store = $this->store();

        $output = $this->imageStep($store)->run(
            $this->runStep(),
            new AiRun,
            ['Cover', 'Middle', 'Closing'],
            'en-GB',
            ImageOptions::default(),
            fn (?string $brief, int $position) => new ImageContext($brief, null, $position),
        );

        $this->assertSame([1, 2, 3], array_column($store->persisted, 'position'));
        $this->assertSame(['Slide 1: Cover', 'Slide 2: Middle', 'Slide 3: Closing'], array_column($store->persisted, 'prompt'));
        $this->assertSame([
            ['position' => 1, 'file_id' => 101, 'file_uuid' => 'fresh-1'],
            ['position' => 2, 'file_id' => 102, 'file_uuid' => 'fresh-2'],
            ['position' => 3, 'file_id' => 103, 'file_uuid' => 'fresh-3'],
        ], $output['images']);

        Image::assertGenerated(fn (SdkImagePrompt $prompt) => $prompt->prompt === 'Slide 2: Middle');
    }

    public function test_a_slot_that_already_has_a_file_is_kept_and_not_paid_for_again(): void
    {
        Image::fake();

        $kept = new AiRunStepFile(['position' => 2, 'workspace_file_id' => 55]);
        $kept->setRelation('file', new WorkspaceFile(['uuid' => 'kept-2']));

        $store = $this->store([2 => $kept]);

        $output = $this->imageStep($store)->run(
            $this->runStep(),
            new AiRun,
            ['Cover', 'Middle', 'Closing'],
            'en-GB',
            ImageOptions::default(),
            fn (?string $brief, int $position) => new ImageContext($brief, null, $position),
        );

        $this->assertSame([1, 3], array_column($store->persisted, 'position'));
        $this->assertSame([
            ['position' => 1, 'file_id' => 101, 'file_uuid' => 'fresh-1'],
            ['position' => 2, 'file_id' => 55, 'file_uuid' => 'kept-2'],
            ['position' => 3, 'file_id' => 103, 'file_uuid' => 'fresh-3'],
        ], $output['images']);

        Image::assertNotGenerated(fn (SdkImagePrompt $prompt) => $prompt->contains('Middle'));
    }

    public function test_the_context_callable_decides_the_placeholders(): void
    {
        Image::fake();

        $store = $this->store();

        $this->imageStep($store)->run(
            $this->runStep(),
            new AiRun,
            [null],
            'en-GB',
            ImageOptions::default(),
            fn (?string $brief, int $position) => new ImageContext('from the run type', null, $position),
        );

        $this->assertSame(['Slide 1: from the run type'], array_column($store->persisted, 'prompt'));
    }

    public function test_describe_carries_the_last_prompt_and_every_file(): void
    {
        Image::fake();

        $step = $this->imageStep($this->store());

        $step->run($this->runStep(), new AiRun, ['A', 'B'], 'en-GB', ImageOptions::default(),
            fn (?string $brief, int $position) => new ImageContext($brief, null, $position));

        $described = $step->describe();

        $this->assertSame('image', $described['modality']);
        $this->assertSame('Slide 2: B', $described['prompt']);
        $this->assertSame(['fresh-1', 'fresh-2'], $described['files']);
    }
}
