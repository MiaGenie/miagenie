<?php

namespace App\Genie\Images;

use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\AiRunStepFile;

/**
 * Runs an image step: one generated file per brief, each in its own slot.
 *
 * A slot that already holds a file is left alone, so a step retried after failing half-way only
 * pays for the images it is still missing. The step's output lists every slot, present or fresh.
 */
class ImageStep
{
    protected ?ImagePrompt $lastPrompt = null;

    /**
     * @var list<array{position: int, file_id: int|null, file_uuid: string|null}>
     */
    protected array $written = [];

    public function __construct(
        protected ImageGenerator $generator = new ImageGenerator,
        protected ImageStore $store = new ImageStore,
    ) {}

    /**
     * @param  list<string|null>  $briefs  One entry per image; null when the message alone says what to draw.
     * @param  callable(string|null $brief, int $position): ImageContext  $context  The run type's placeholders for a slot.
     * @return array{images: list<array{position: int, file_id: int|null, file_uuid: string|null}>}
     */
    public function run(
        AiRunStep $runStep,
        AiRun $run,
        array $briefs,
        string $locale,
        ImageOptions $options,
        callable $context,
    ): array {
        $this->written = [];

        foreach (array_values($briefs) as $index => $brief) {
            $position = $index + 1;

            $existing = $this->store->current($runStep, $position);

            if ($existing) {
                $this->written[] = $this->entry($existing);

                continue;
            }

            $prompt = new ImagePrompt($run, $runStep->step, $locale, $context($brief, $position));

            $this->lastPrompt = $prompt;

            $response = $this->generator->generate($prompt, $options);

            $this->written[] = $this->entry(
                $this->store->persist($runStep, $run, $position, $prompt, $response->firstImage()),
            );
        }

        return ['images' => $this->written];
    }

    /**
     * What the log records as the request: the last prompt sent, and every file the step ended with.
     *
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        return array_merge(
            $this->lastPrompt?->describe() ?? ['modality' => 'image'],
            ['files' => array_column($this->written, 'file_uuid')],
        );
    }

    /**
     * @return array{position: int, file_id: int|null, file_uuid: string|null}
     */
    protected function entry(AiRunStepFile $file): array
    {
        return [
            'position' => (int) $file->position,
            'file_id' => $file->workspace_file_id,
            'file_uuid' => $file->file?->uuid,
        ];
    }
}
