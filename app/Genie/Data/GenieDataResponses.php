<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData as AbstractThreadActionData;
use App\Concerns\GenieParser;
use App\Contracts\GenieDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\GenieType;
use App\Enums\RuleSubType;
use App\Enums\RunResponseStatus;
use App\Enums\VersionGroupType;
use App\Models\Assistant;
use App\Models\Briefing;
use App\Models\Competitor;
use App\Models\RuleStep;
use App\Models\RunCompetitor;
use App\Models\RunResponse;
use App\Models\Strategy;
use App\Models\Thread;
use App\Models\ThreadRun;
use App\Models\Vector;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieDataResponses extends AbstractThreadActionData implements GenieDataContract
{
    use GenieParser;

    protected const TYPE = 'THREAD';

    /**
     * @var RunResponse
     */
    private RunResponse $runResponse;

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
        WorkspaceManager::setCurrent($this->runResponse->run->workspace);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $assistant = $this->runResponse->step->assistant;

        $data = [
            'include' => null,
            'input' => $this->getMessage(),
            'instructions' => $assistant->instructions,
            'max_output_tokens' => null,
            'model' => $assistant->model,
            'previous_response_id' => $this->getPreviousResponseId(),
//            'prompt' => '',
            'reasoning' => $assistant->reasoning_effort,
            'store' => true,
            'temperature' => $assistant->temperature ? (float)$assistant->temperature : null,
            'top_p' => $assistant->top_p ? (float)$assistant->top_p : null
        ];

/*        if ($assistant->vector_id) {
            $vectorStoreIds = Vector::find($assistant->vector_id)?->vector_provider_id;
            $tools['type'] = 'file_search';
            $tools['vector_store_ids'] = [$vectorStoreIds];
            $data['tools'] = [$tools];
        }

        $data['tool_choice'] = 'required';*/

        $format['type'] = $assistant->response_format;
        if ($assistant->response_format === 'json_schema') {
            $format['name'] = Str::snake($assistant->name);
            $format['strict'] = true;
            $format['schema'] = json_decode($assistant->json_schema, true);
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
     * @return ?string
     */
    private function getPreviousResponseId(): ?string
    {
        $previousResponse = RunResponse::where(['run_id' => $this->runResponse->run->id])
            ->whereKeyNot($this->runResponse->id)
            ->latest('id')
            ->value('response_provider_id');
        return $previousResponse;
    }

    /**
     * @return ?GenieSyncAction
     */
    public function nextAction(): ?GenieSyncAction
    {
        $status = $this->response['status'] ? RunResponseStatus::fromName($this->response['status']) : null;

        if ($status && ($status->isComplete() || $status->requiresUpdate())) {
            return GenieSyncAction::UPDATE;
        }

        return $this->nextAction;
    }

    /**
     * @return string
     */
    private function getMessage(): string
    {
        $replacements = $this->getReplacements();

        return $this->parseContent($this->runResponse->step->message, $replacements);
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

    public function getRequest(): array
    {
        return $this->getData();
    }
}
