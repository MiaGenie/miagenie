<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Ai\StepResponse;
use App\Concerns\GenieParser;
use App\Contracts\GenieDataContract;
use App\Contracts\GenieStepDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\RuleType;
use App\Enums\VersionGroupType;
use App\Genie\Schema\StepSchemaBuilder;
use App\Models\RuleStep;
use App\Models\RunResponse;
use App\Models\Strategy;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Arr;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieDataResponses extends GenieData implements GenieDataContract, GenieStepDataContract
{
    use GenieParser;

    private RunResponse $runResponse;

    private ?RunResponse $previousRunResponse;

    private ?RuleStep $lastStep;

    private string $locale;

    public function __construct(
        RunResponse $runResponse,
        GenieSyncAction $action,
    ) {
        parent::__construct($runResponse, $action);
        $this->runResponse = $runResponse;
        $this->previousRunResponse = $this->getPreviousResponse();
        WorkspaceManager::setCurrent($this->runResponse->run->workspace);
        $this->locale = $this->runResponse->run->workspace->locale ?? app()->getFallbackLocale();
    }

    /**
     * A record of what was sent, for `genie_logs`.
     *
     * The agent itself carries the generation options, so this is descriptive rather than the
     * literal request body — which is the point: nothing here is OpenAI request vocabulary.
     */
    public function getData(): array
    {
        $step = $this->runResponse->step;
        $profile = $step->modelProfile;

        return [
            'provider' => $profile?->provider,
            'model' => $profile?->explicitModel() ?? $profile?->model_tier?->value,
            'instructions' => $step->getTranslation('instructions', $this->locale),
            'prompt' => $this->getPrompt(),
            'response_format' => $step->response_format,
            'schema' => app(StepSchemaBuilder::class)->tryForStep($step, $this->locale),
        ];
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getPrompt(): string
    {
        return $this->getMessage();
    }

    public function getProviderIdField(): string
    {
        return 'response_provider_id';
    }

    private function getPreviousResponse(): ?RunResponse
    {
        $previousRunResponse = RunResponse::where(['run_id' => $this->runResponse->run->id])
            ->whereKeyNot($this->runResponse->id)
            ->latest('id')
            ->first();

        return $previousRunResponse;
    }

    public function nextAction(): ?GenieSyncAction
    {
        $status = StepResponse::status($this->response['status'] ?? null);

        if ($status && ! $this->runResponse->step->requires_review && ($status->isComplete() || $status->requiresUpdate())) {
            return GenieSyncAction::UPDATE;
        }

        return $this->nextAction;
    }

    public function getRuleType(): RuleType
    {
        return $this->model->step->rule->rule_type;
    }

    private function getMessage(): string
    {
        $reviewMsg = $this->getReviewMessage();
        $msg = $this->runResponse->step->getTranslation('message', $this->locale);
        $replacements = $this->getReplacements();

        return $this->parseContent($reviewMsg.$msg, $replacements);
    }

    private function getReplacements(): array
    {
        return array_merge(
            $this->getBriefingReplacements(),
            $this->getStrategyReplacements(),
        );
    }

    private function getBriefingReplacements(): array
    {
        $briefing = $this->runResponse->run->runBriefing->briefing->content;
        $briefing = $this->formatFieldOptions($briefing, 'BRIEFINGS');

        return Arr::prependKeysWith($briefing, 'briefings.');
    }

    private function getStrategyReplacements(): array
    {
        $strategy = Strategy::where(['workspace_id' => $this->runResponse->run->workspace_id])
            ->latest()
            ->first()?->content;

        if (! $strategy) {
            return [];
        }

        return Arr::prependKeysWith(
            array_map($this->flattenStrategyValue(...), $strategy),
            'strategy.'
        );
    }

    /**
     * Render a strategy answer for the prompt.
     *
     * A field whose sub-field tree nests objects cannot be flattened by the parser's implode,
     * which would emit the literal string "Array", so anything deeper than a list of strings is
     * passed through as JSON instead.
     */
    private function flattenStrategyValue(mixed $value): mixed
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

    private function formatFieldOptions(array $content, string $type): array
    {
        foreach ($content as $key => $item) {
            $content[$key] = is_array($item) ? $this->getFormatedFieldOptions($key, $item, $type) : $item;
        }

        return $content;
    }

    private function getFormatedFieldOptions(string $key, array $item, string $type): string
    {
        $field = VersionField::where([
            'version_id' => $this->runResponse->run->rule->version_id,
            'group_type' => VersionGroupType::fromName($type),
            'code_name' => $key,
        ])->first();

        if (! $field) {
            return '';
        }

        if ($field->field_type->name === 'FILE') {
            return $item['path'] ?? '';
        }

        $fieldOptions = VersionFieldOption::where('field_id', $field->id)->get()
            ->pluck('name', 'code_name')->toArray();

        // Stored briefings can hold option codes that were later renamed or removed, so an
        // unknown code falls back to the code itself rather than resolving to null.
        $labels = array_map(
            fn ($value) => is_string($value) ? ($fieldOptions[$value] ?? $value) : null,
            array_values($item)
        );

        return implode(', ', array_filter($labels, fn ($label) => filled($label)));
    }

    public function getRequest(): array
    {
        if (count($this->request) > 0) {
            return $this->request;
        }

        $this->request = $this->getData();

        return $this->request;
    }

    public function setResponseStatus(): void
    {
        parent::setResponseStatus();
        if ($this->response['error'] ?? false) {
            $this->error = true;
        }
    }

    public function getReviewMessage(): string
    {
        $reviewMsg = '';
        if ($this->previousRunResponse?->step->requires_review && $this->previousRunResponse?->runResponseReview?->id) {
            $reviewMsg = $this->previousRunResponse->step->getTranslation('instructions', $this->locale)."\n";
        }

        return $reviewMsg;
    }
}
