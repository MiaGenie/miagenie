<?php

namespace App\Genie\Strategy;

use App\Concerns\GenieParser;
use App\Models\AiRun;
use App\Models\RuleStep;
use Illuminate\Support\Arr;

/**
 * Builds the message sent for one step of a run.
 */
class StrategyPrompt
{
    use GenieParser;

    public function __construct(
        protected AiRun $run,
        protected RuleStep $step,
        protected string $locale,
    ) {}

    public function text(): string
    {
        return $this->parseContent(
            $this->reviewMessage().$this->step->getTranslation('message', $this->locale),
            $this->replacements(),
        );
    }

    /**
     * A record of what was sent, for the run log.
     */
    public function describe(): array
    {
        $profile = $this->step->modelProfile;

        return [
            'provider' => $profile?->provider,
            'model' => $profile?->explicitModel() ?? $profile?->model_tier?->value,
            'instructions' => $this->step->getTranslation('instructions', $this->locale),
            'prompt' => $this->text(),
            'response_format' => $this->step->response_format,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function replacements(): array
    {
        return array_merge($this->briefingReplacements(), $this->strategyReplacements());
    }

    /**
     * The briefing, answer by answer, plus `FULL` — the whole thing in one block, for a step that
     * wants the customer's context rather than a handful of named fields.
     *
     * `FULL` cannot shadow a field: a code_name goes through App\Rules\SnakeCase and is always
     * lowercase.
     *
     * @return array<string, mixed>
     */
    protected function briefingReplacements(): array
    {
        $briefing = new BriefingContext($this->run, $this->locale);

        return Arr::prependKeysWith(
            array_merge($briefing->answers(), ['FULL' => $briefing->full()]),
            'briefings.'
        );
    }

    /**
     * The strategy written so far.
     *
     * Scoped to the run's own strategy rather than "the newest strategy in the workspace", which
     * is what the previous implementation looked up and which went wrong as soon as a workspace
     * had more than one.
     *
     * @return array<string, mixed>
     */
    protected function strategyReplacements(): array
    {
        $content = $this->run->strategy?->content;

        if (blank($content)) {
            return [];
        }

        return Arr::prependKeysWith(array_map($this->flatten(...), $content), 'strategy.');
    }

    /**
     * A field whose sub-field tree nests objects cannot be flattened by the parser's implode,
     * which would emit the literal string "Array", so anything deeper than a list of strings is
     * passed through as JSON instead.
     */
    protected function flatten(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $item) {
            if (is_array($item)) {
                return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        return $value;
    }

    /**
     * Only sent when the previous step required review and had review changes
     */
    protected function reviewMessage(): string
    {
        $previous = $this->run->steps()
            ->reorder()
            ->where('position', '<', $this->step->position)
            ->latest('position')
            ->first();

        if (! $previous?->step?->requires_review || ! $previous->review) {
            return '';
        }

        $parts = array_filter([
            $previous->step->getTranslation('review_message_system', $this->locale),
            $this->renderReviewed($previous->review->reviewed ?? []),
        ], fn ($part) => filled($part));

        return $parts === [] ? '' : implode("\n", $parts)."\n";
    }

    /**
     * The reviewed values, keyed by the field they belong to.
     *
     * @param  array<string, mixed>  $reviewed
     */
    protected function renderReviewed(array $reviewed): string
    {
        $lines = [];

        foreach ($reviewed as $codeName => $value) {
            $rendered = $this->flatten($value);

            $lines[] = $codeName.': '.(is_array($rendered) ? implode("\n", $rendered) : $rendered);
        }

        return implode("\n", $lines);
    }
}
