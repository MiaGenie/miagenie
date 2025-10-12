<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData;
use App\Concerns\GenieParser;
use App\Contracts\GenieDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\RuleSubType;
use App\Enums\RunResponseStatus;
use App\Enums\VersionGroupType;
use App\Models\Briefing;
use App\Models\RuleStep;
use App\Models\RunResponse;
use App\Models\Strategy;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Arr;
use Illuminate\Support\Str;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieDataResponses extends GenieData implements GenieDataContract
{
    use GenieParser;

    /**
     * @var RunResponse
     */
    private RunResponse $runResponse;

    /**
     * @var ?RunResponse
     */
    private ?RunResponse $previousRunResponse;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $lastStep;

    /**
     * @param RunResponse $runResponse
     * @param GenieSyncAction $action
     */
    public function __construct(
        RunResponse $runResponse,
        GenieSyncAction $action,
    ) {
        parent::__construct($runResponse, $action);
        $this->runResponse = $runResponse;
        $this->previousRunResponse = $this->getPreviousResponse();
        WorkspaceManager::setCurrent($this->runResponse->run->workspace);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $step = $this->runResponse->step;

        $data = [
            'include' => null,
            'input' => $this->getMessage(),
            'instructions' => $step->instructions,
            'max_output_tokens' => null,
            'model' => $step->ai_model,
            'previous_response_id' => $this->getPreviousResponseId(),
            'reasoning' => $step->reasoning_effort,
            'store' => true,
            'temperature' => $step->temperature ? (float)$step->temperature : null,
            'top_p' => $step->top_p ? (float)$step->top_p : null
        ];

/*        if ($step->vector_id) {
            $vectorStoreIds = Vector::find($step->vector_id)?->vector_provider_id;
            $tools['type'] = 'file_search';
            $tools['vector_store_ids'] = [$vectorStoreIds];
            $data['tools'] = [$tools];
        }

        $data['tool_choice'] = 'required';*/

        $format['type'] = $step->response_format;
        if ($step->response_format === 'json_schema') {
            $name = preg_replace("/[^\sa-zA-Z0-9_-]/", "", $step->name);
            $format['name'] = Str::snake($name);
            $format['strict'] = true;
            $format['schema'] = json_decode($step->json_schema, true);
        }
        $data['text']['format'] = $format;

        return $data;
    }

    /**
     * @return string
     */
    public function getProviderIdField(): string
    {
        return 'response_provider_id';
    }

    /**
     * @return ?RunResponse
     */
    private function getPreviousResponse(): ?RunResponse
    {
        $previousRunResponse = RunResponse::where(['run_id' => $this->runResponse->run->id])
            ->whereKeyNot($this->runResponse->id)
            ->latest('id')
            ->first();

        return $previousRunResponse;
    }

    /**
     * @return ?string
     */
    private function getPreviousResponseId(): ?string
    {
        return $this->previousRunResponse?->response_provider_id;
    }

    /**
     * @return ?GenieSyncAction
     */
    public function nextAction(): ?GenieSyncAction
    {
        $status = $this->response['status'] ? RunResponseStatus::fromName($this->response['status']) : null;

        if ($status && !$this->runResponse->step->requires_review && ($status->isComplete() || $status->requiresUpdate())) {
            return GenieSyncAction::UPDATE;
        }

        return $this->nextAction;
    }

    /**
     * @return string
     */
    private function getMessage(): string
    {
        $reviewMsg = $this->getReviewMessage();
        $replacements = $this->getReplacements();

        return $this->parseContent($reviewMsg . $this->runResponse->step->message, $replacements);
    }

    /**
     * @return array
     */
    private function getReplacements(): array
    {
        $briefings = $this->getBriefingReplacements();
        $competitors = $this->runResponse->step->rule_sub_type === RuleSubType::COMPETITORS ? $this->getCompetitorReplacements() : [];
        $strategy = $this->getStrategyReplacements();

        return array_merge($briefings, $competitors, $strategy);
    }

    /**
     * @return array
     */
    private function getBriefingReplacements(): array
    {
        $briefing = Briefing::where(['workspace_id' => $this->runResponse->run->workspace_id])->latest()->first()?->content;
        $briefing = $this->translateFieldOptions($briefing, 'BRIEFINGS');

        return Arr::prependKeysWith($briefing, 'briefings.');
    }

    /**
     * @return array
     */
    private function getCompetitorReplacements(): array
    {
        $competitor = $this->runResponse->runCompetitor->competitor->content;
        $competitor = $this->translateFieldOptions($competitor, 'COMPETITORS');

        return Arr::prependKeysWith($competitor, 'competitors.');
    }

    /**
     * @return array
     */
    private function getStrategyReplacements(): array
    {
        $strategy = Strategy::where(['workspace_id' => $this->runResponse->run->workspace_id])->latest()->first()?->content;

        return $strategy ? Arr::prependKeysWith(
            Strategy::where(['workspace_id' => $this->runResponse->run->workspace_id])->latest()->first()?->content,
            'strategy.'
        ) : [];
    }

    /**
     * @param array $content
     * @param string $type
     * @return array
     */
    private function translateFieldOptions(array $content, string $type): array
    {
        foreach ($content as $key => $item) {
            $content[$key] = is_array($item) ? $this->getTranslatedFieldOptions($key, $item, $type) : $item;
        }
        return $content;
    }

    /**
     * @param string $key
     * @param array $item
     * @param string $type
     * @return string
     */
    private function getTranslatedFieldOptions(string $key, array $item, string $type): string
    {
        $field = VersionField::where([
            'version_id' => $this->runResponse->run->rule->version_id,
            'group_type' => VersionGroupType::fromName($type),
            'code_name' => $key,
        ])->first();

        $fieldOptions = VersionFieldOption::where('field_id', $field->id)->get()
            ->pluck('name', 'code_name')->toArray();

        if (sizeof($item) === 1) {
            return $fieldOptions[$item[0]];
        }

        $item = array_map(function ($value) use ($fieldOptions) {
            return $fieldOptions[$value];
        }, $item);

        return implode(', ', $item);
    }

    /**
     * @return array
     */
    public function getRequest(): array
    {
        if (sizeof($this->request) > 0) {
            return $this->request;
        }

        $this->request = $this->getData();

        return $this->request;
    }

    /**
     * @return void
     */
    public function setResponseStatus(): void
    {
        parent::setResponseStatus();
        if ($this->response['error'] ?? false) {
            $this->error = true;
        }
    }

    /**
     * @return string
     */
    public function getReviewMessage(): string
    {
        $reviewMsg = '';
        if ($this->previousRunResponse?->step->requires_review && $this->previousRunResponse?->runResponseReview?->id) {
            $reviewMsg = $this->previousRunResponse->step->review_message_system. "/n";
            //$reviewMsg = implode("\n", $this->previousRunResponse->runResponseReview->reviewed) . "\n";

        }
        return $reviewMsg;
    }
}
