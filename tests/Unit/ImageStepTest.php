<?php

namespace Tests\Unit;

use App\Ai\StepAgentFactory;
use App\Enums\ModelTier;
use App\Genie\Images\ImageCallLog;
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
use Illuminate\Support\Collection;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Image;
use Laravel\Ai\Prompts\ImagePrompt as SdkImagePrompt;
use Laravel\Ai\Responses\Data\GeneratedImage;
use Laravel\Ai\Responses\Data\ImageUsage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\ImageResponse;
use RuntimeException;
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

    /**
     * A call log that records to an array instead of the database.
     */
    protected function callLog(): ImageCallLog
    {
        return new class extends ImageCallLog
        {
            public array $records = [];

            public function record(AiRunStep $runStep, ImagePrompt $prompt, ?AiRunStepFile $file, int $duration, ?string $error = null, ?string $mode = null, ?AiRunStepFile $parent = null, ?Usage $usage = null): void
            {
                $this->records[] = [
                    'run_step' => $runStep->id,
                    'prompt' => $prompt->text(),
                    'file' => $file?->file?->uuid,
                    'error' => $error,
                    'mode' => $mode,
                    'parent' => $parent?->id,
                    'tokens' => $usage ? [$usage->inputTokens, $usage->outputTokens] : null,
                ];
            }
        };
    }

    protected function runStep(): AiRunStep
    {
        $step = new RuleStep(['name' => 'Slides', 'message' => ['en-GB' => 'Slide {{{position}}}: {{{brief}}}']]);
        $step->setRelation('modelProfile', new ModelProfile(['provider' => 'openai', 'model_tier' => ModelTier::DEFAULT]));

        $runStep = new AiRunStep(['step_id' => 7]);
        $runStep->id = 5;
        $runStep->setRelation('step', $step);

        return $runStep;
    }

    protected function imageStep(ImageStore $store, ?ImageCallLog $log = null): ImageStep
    {
        $agents = $this->createMock(StepAgentFactory::class);
        $agents->method('prepare')->willReturn(Lab::OpenAI);
        $agents->method('timeout')->willReturn(60);
        $agents->method('imageModel')->willReturn(null);

        return new ImageStep(new ImageGenerator($agents), $store, $log ?? $this->callLog());
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

    public function test_every_call_made_is_logged_on_the_run_step_and_reused_slots_are_not(): void
    {
        Image::fake();

        $kept = new AiRunStepFile(['position' => 2, 'workspace_file_id' => 55]);
        $kept->setRelation('file', new WorkspaceFile(['uuid' => 'kept-2']));

        $log = $this->callLog();

        $this->imageStep($this->store([2 => $kept]), $log)->run(
            $this->runStep(),
            new AiRun,
            ['Cover', 'Middle', 'Closing'],
            'en-GB',
            ImageOptions::default(),
            fn (?string $brief, int $position) => new ImageContext($brief, null, $position),
        );

        $this->assertCount(2, $log->records);
        $this->assertSame([5, 5], array_column($log->records, 'run_step'));
        $this->assertSame(['Slide 1: Cover', 'Slide 3: Closing'], array_column($log->records, 'prompt'));
        $this->assertSame(['fresh-1', 'fresh-3'], array_column($log->records, 'file'));
        $this->assertSame([null, null], array_column($log->records, 'error'));
    }

    public function test_a_failed_call_is_logged_with_its_error_before_the_step_fails(): void
    {
        Image::fake([new ImageResponse(new Collection, new ImageUsage, new Meta('openai', 'gpt-image-2'))]);

        $log = $this->callLog();

        try {
            $this->imageStep($this->store(), $log)->run($this->runStep(), new AiRun, ['Cover'], 'en-GB', ImageOptions::default(),
                fn (?string $brief, int $position) => new ImageContext($brief, null, $position));
            $this->fail('The empty response was accepted.');
        } catch (RuntimeException) {
        }

        $this->assertCount(1, $log->records);
        $this->assertNull($log->records[0]['file']);
        $this->assertStringContainsString('returned no image', $log->records[0]['error']);
    }

    public function test_every_call_is_logged_with_the_tokens_it_consumed(): void
    {
        Image::fake([
            $this->imageResponse(new ImageUsage(inputTokens: 120, outputTokens: 4000)),
            $this->imageResponse(new ImageUsage(inputTokens: 90, outputTokens: 3000)),
        ]);

        $log = $this->callLog();

        $this->imageStep($this->store(), $log)->run($this->runStep(), new AiRun, ['Cover', 'Closing'], 'en-GB', ImageOptions::default(),
            fn (?string $brief, int $position) => new ImageContext($brief, null, $position));

        $this->assertSame([[120, 4000], [90, 3000]], array_column($log->records, 'tokens'));
    }

    public function test_an_image_that_could_not_be_stored_is_logged_with_the_tokens_already_paid(): void
    {
        Image::fake([$this->imageResponse(new ImageUsage(inputTokens: 120, outputTokens: 4000))]);

        $store = new class([]) extends ImageStore
        {
            public function __construct(public array $slots) {}

            public function current(AiRunStep $runStep, int $position): ?AiRunStepFile
            {
                return null;
            }

            public function persist(AiRunStep $runStep, AiRun $run, int $position, ImagePrompt $prompt, GeneratedImage $image, ?AiRunStepFile $parent = null, ?string $mode = null): AiRunStepFile
            {
                throw new RuntimeException('Disk full.');
            }
        };

        $log = $this->callLog();

        try {
            $this->imageStep($store, $log)->run($this->runStep(), new AiRun, ['Cover'], 'en-GB', ImageOptions::default(),
                fn (?string $brief, int $position) => new ImageContext($brief, null, $position));
            $this->fail('The failed write was swallowed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Disk full.', $exception->getMessage());
        }

        $this->assertCount(1, $log->records);
        $this->assertSame('Disk full.', $log->records[0]['error']);
        $this->assertSame([120, 4000], $log->records[0]['tokens']);
    }

    public function test_a_call_that_never_answered_is_logged_without_tokens(): void
    {
        $log = $this->callLog();

        $generator = $this->createMock(ImageGenerator::class);
        $generator->method('generate')->willThrowException(new RuntimeException('Provider down.'));

        try {
            (new ImageStep($generator, $this->store(), $log))->run($this->runStep(), new AiRun, ['Cover'], 'en-GB', ImageOptions::default(),
                fn (?string $brief, int $position) => new ImageContext($brief, null, $position));
            $this->fail('The failed call was swallowed.');
        } catch (RuntimeException) {
        }

        $this->assertSame('Provider down.', $log->records[0]['error']);
        $this->assertNull($log->records[0]['tokens']);
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

    protected function imageResponse(ImageUsage $usage): ImageResponse
    {
        return new ImageResponse(
            new Collection([new GeneratedImage(base64_encode('png-bytes'), 'image/png')]),
            $usage,
            new Meta('openai', 'gpt-image-2'),
        );
    }
}
