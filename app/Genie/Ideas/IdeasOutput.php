<?php

namespace App\Genie\Ideas;

use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Genie\Runs\StepOutput;
use App\Models\AiRunStep;
use App\Models\Idea;
use App\Support\PublishTargets;
use RuntimeException;

/**
 * Turns an ideas step's structured response into Idea rows.
 */
class IdeasOutput implements StepOutput
{
    /**
     * @param  PublishTargets|null  $targets  the workspace's providers; read from its accounts when not given
     */
    public function __construct(protected ?PublishTargets $targets = null) {}

    /**
     * @param  array<string, mixed>  $structured
     */
    public function write(AiRunStep $runStep, array $structured): void
    {
        foreach ($this->resolve($runStep, $structured) as $attributes) {
            Idea::create($attributes);
        }
    }

    /**
     * One row of attributes per idea in the response.
     *
     * A pillar or stage the request fixed is taken from the request rather than the response:
     * the schema's enum already narrows the model to that one value, and reading it back from the
     * request is what keeps a stray answer from mislabelling the idea. Only when the request left
     * the choice open is the model's answer the one that counts.
     *
     * @param  array<string, mixed>  $structured
     * @return list<array<string, mixed>>
     */
    public function resolve(AiRunStep $runStep, array $structured): array
    {
        $ideas = $structured[IdeaSchema::ROOT] ?? null;

        if (! is_array($ideas) || ! array_is_list($ideas)) {
            throw new RuntimeException("Step [{$runStep->step_id}] returned no list of ideas.");
        }

        $run = $runStep->run;
        $request = IdeasRequest::fromRun($run);
        $format = $runStep->step?->post_format;

        // A generated idea targets every provider on offer for its format; the reviewer prunes.
        $targets = $this->targets ?? PublishTargets::forWorkspace($run->workspace_id);
        $publishTo = $targets->available($format) ?: null;

        $rows = [];

        foreach ($ideas as $idea) {
            if (! is_array($idea)) {
                continue;
            }

            $rows[] = [
                'workspace_id' => $run->workspace_id,
                'strategy_id' => $run->strategy_id,
                'run_step_id' => $runStep->id,
                'theme' => $this->string($idea['title'] ?? null),
                'objective' => $this->string($idea['objective'] ?? null),
                'suggested_content' => $this->string($idea['suggested_content'] ?? null),
                'multimedia_suggestion' => $this->string($idea['multimedia_suggestion'] ?? null),
                'production_instructions' => $this->string($idea['production_instructions'] ?? null),
                'images' => $format?->imageRange() ? $this->strings($idea['images'] ?? null) : null,
                'publish_to' => $publishTo,
                'post_format' => $format,
                'funnel_stage' => $request->wantsAnyStage()
                    ? FunnelStage::fromTitle((string) ($idea['funnel_stage'] ?? ''))
                    : $request->funnelStage,
                'content_pillar' => $request->wantsAnyPillar()
                    ? $this->string($idea['content_pillar'] ?? null)
                    : $request->contentPillar,
                'status' => IdeaStatus::PENDING_REVIEW,
                'source' => IdeaSource::GENIE,
            ];
        }

        return $rows;
    }

    protected function string(mixed $value): ?string
    {
        return is_scalar($value) && filled($value) ? (string) $value : null;
    }

    /**
     * A list of texts in the order given, with anything blank or not a text left out.
     *
     * @return list<string>
     */
    protected function strings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $strings = array_map($this->string(...), array_values($value));

        return array_values(array_filter($strings, fn (?string $string) => $string !== null));
    }
}
