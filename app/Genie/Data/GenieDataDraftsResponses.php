<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Concerns\GenieParser;
use App\Concerns\GenieSchemaParser;
use App\Contracts\GenieDataContract;
use App\Contracts\GenieStepDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\RuleSubType;
use App\Enums\RuleType;
use App\Enums\VersionGroupType;
use App\Genie\Schema\StepSchemaBuilder;
use App\Models\Briefing;
use App\Models\RuleStep;
use App\Models\RunResponse;
use App\Models\Strategy;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Arr;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieDataDraftsResponses extends GenieData implements GenieDataContract, GenieStepDataContract
{
    use GenieParser;
    use GenieSchemaParser;

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
        WorkspaceManager::setCurrent($this->runResponse->run->workspace);
        $this->previousRunResponse = $this->getPreviousResponse();
        $this->locale = $this->runResponse->run->workspace->locale ?? app()->getFallbackLocale();
    }

    /**
     * A record of what was sent, for `genie_logs`. The agent carries the generation options.
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

    /**
     * The run's preceding response, used only to carry review feedback forward.
     *
     * This used to encode three different `link_upstream` chaining strategies feeding
     * `previous_response_id`. That concept does not exist without the Responses API, so the
     * lookup is now simply "the last response in this run".
     */
    private function getPreviousResponse(): ?RunResponse
    {
        return RunResponse::where(['run_id' => $this->runResponse->run->id])
            ->whereKeyNot($this->runResponse->id)
            ->latest('id')
            ->first();
    }

    public function nextAction(): ?GenieSyncAction
    {
        return GenieSyncAction::UPDATE;
    }

    public function getRuleType(): RuleType
    {
        return $this->model->step->rule->rule_type;
    }

    private function getMessage(): string
    {
        $msg = $this->runResponse->step->getTranslation('message', $this->locale);

        $replacements = $this->getReplacements();
        $strategy = $this->getStrategyReplacements();
        $schemas = $this->getStrategySchemas();

        $text = $this->parseContent($msg, $replacements);
        $text = $this->parseSchemaContent($text, $strategy, $schemas);

        return $text;
    }

    private function getReplacements(): array
    {
        $briefings = $this->getBriefingReplacements();
        $competitors = $this->runResponse->step->rule_sub_type === RuleSubType::COMPETITORS ? $this->getCompetitorReplacements() : [];
        $ideas = $this->getIdeasReplacements();

        return array_merge($briefings, $competitors, $ideas);
    }

    private function getIdeasReplacements(): array
    {
        $idea = $this->runResponse->runIdeaResponse?->runIdea->idea->only(
            $this->runResponse->runIdeaResponse?->runIdea->idea->getGenieFields()
        );

        return $idea ? Arr::prependKeysWith($idea, 'idea.') : [];
    }

    private function getBriefingReplacements(): array
    {
        $briefing = Briefing::where(['workspace_id' => $this->runResponse->run->workspace_id])->latest()->first()?->content;
        $briefing = $this->formatFieldOptions($briefing, 'BRIEFINGS');

        return Arr::prependKeysWith($briefing, 'briefings.');
    }

    private function getCompetitorReplacements(): array
    {
        $competitor = $this->runResponse->runCompetitor->competitor->content;
        $competitor = $this->formatFieldOptions($competitor, 'COMPETITORS');

        return Arr::prependKeysWith($competitor, 'competitors.');
    }

    private function getStrategyReplacements(): array
    {
        $strategy['strategy'] = Strategy::where(['workspace_id' => $this->runResponse->run->workspace_id])->latest()->first()?->content;

        return $strategy;
    }

    private function getStrategySchemas(): array
    {
        $schemas = $this->runResponse->run->runStrategy->strategy->run->rule->steps->map(function (RuleStep $step) {
            return $step->getTranslation('json_schema', $this->locale);
        });

        $schemas = $schemas->reduce(function (array $list, $item) {
            $item = json_decode($item, true);
            $list['strategy'] = array_merge($list['strategy'], $item['properties']);

            return $list;
        }, ['strategy' => []]);

        return $schemas;
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

        $fieldOptions = VersionFieldOption::where('field_id', $field->id)->get()
            ->pluck('name', 'code_name')->toArray();

        if (count($item) === 1) {
            return $fieldOptions[$item[0]];
        }

        if ($field->field_type->name === 'FILE') {
            return $item['path'] ?? '';
        }

        $item = array_map(function ($value) use ($fieldOptions) {
            if (isset($fieldOptions[$value])) {
                return $fieldOptions[$value];
            }
        }, $item);

        return implode(', ', $item);
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

    private function ruleInitialStep(): ?RuleStep
    {
        foreach ($this->runResponse->run->rule->steps as $ruleStep) {
            if (str_contains($ruleStep->rule_sub_type->name, 'INITIAL')) {
                return $ruleStep;
            }
        }

        return null;
    }
}
