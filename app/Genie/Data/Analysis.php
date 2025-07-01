<?php

namespace App\Genie\Data;

use App\Abstracts\GenieData as AbstractThreadActionData;
use App\Concerns\GenieParser;
use App\Contracts\GenieDataContract;
use App\Enums\VersionGroupType;
use App\Models\Assistant;
use App\Models\Briefing;
use App\Models\Competitor;
use App\Models\RuleStep;
use App\Models\RunCompetitor;
use App\Models\Strategy;
use App\Models\Thread;
use App\Models\ThreadRun;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Arr;
use Illuminate\Database\Eloquent\Collection;
use Inovector\Mixpost\Facades\WorkspaceManager;

class Analysis extends AbstractThreadActionData implements GenieDataContract
{
    use GenieParser;

    protected const TYPE = 'THREAD';

    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $lastStep;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $currentStep;

    /**
     * @param string $action
     * @param Thread $model
     */
    public function __construct(
        string $action,
        Thread $model
    ) {
        parent::__construct(self::TYPE, $action, $model);
        $this->thread = $model;
        WorkspaceManager::setCurrent($this->thread->workspace);
        $this->lastStep = $this->lastStep();
        $this->currentStep = $this->nextStep($this->lastStep);
        $this->data = $this->getData();
    }

    /**
     * @return Thread
     */
    public function getModel(): Thread
    {
        $model = match ($this->action) {
            'status' => $this
        }
        return $this->thread;
    }

    /**
     * @return string
     */
    public function getModelProviderId(): string
    {
        return $this->thread->thread_provider_id;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $data = [
            'assistant_id' => $this->getAssistantProviderId(),
            'additional_messages' => [[
                'content' => $this->getMessage(),
                'role' => 'user',
            ]],
        ];

        return $data;
    }


    public function getRequest(): array
    {
        $this->request['thread_id'] = $this->thread->thread_provider_id;
        $this->request = array_merge($this->request, $this->data);

        return $this->request;
    }


    public function nextAction(): string
    {
        switch ($this->action) {
            case 'create':
                $this->nextAction = 'update';
                break;
            case 'update':
                $this->nextAction = 'status';
                break;
            case 'status':
                switch (strtoupper($this->response['status'])) {
                    default:
                    case 'QUEUED':
                    case 'IN_PROGRESS':
                        $this->nextAction = 'status';
                        break;
                    case 'COMPLETED':
                        $this->nextAction = 'message';
                        break;
                }
                break;
            case 'message':
                $nextStep = $this->nextStep($this->currentStep);
                $this->nextAction = $nextStep ? 'update' : '';
                break;
        }

        return $this->nextAction;
    }

    /**
     * @return ?RuleStep
     */
    private function lastStep(): ?RuleStep
    {
        if (isset($this->lastStep)) {
            return $this->lastStep;
        }

        $lastRun = $this->lastRun();
        $this->lastStep = $lastRun?->id ? RuleStep::find($lastRun?->step_id) : null;

        return $this->lastStep;
    }


    /**
     * @return ?RuleStep
     */
    public function currentStep(): ?RuleStep
    {
        return $this->currentStep;
    }

    /**
     * @param \App\Models\RuleStep|null $lastStep
     * @return ?RuleStep
     */
    private function nextStep(?RuleStep $lastStep): ?RuleStep
    {
        switch ($lastStep?->rule_sub_type->name) {
            default:
            case null:
            case 'BRIEFINGS':
                return $this->getNextStep();

            case 'COMPETITORS':
                return $this->getNextStepAfterCompetitor();

        }
    }

    /**
     * @return string
     */
    private function getAssistantProviderId(): string
    {
        $assistant = Assistant::find($this->currentStep->assistant_id);

        return $assistant->assistant_provider_id;
    }

    /**
     * @return ?ThreadRun
     */
    public function lastRun(): ?ThreadRun
    {
        return ThreadRun::where(['thread_id' => $this->thread->id])->latest('id')->first();
    }

    /**
     * @return ?RuleStep
     */
    private function getNextStepAfterCompetitor(): ?RuleStep
    {
        $todoCompetitors = $this->getTodoCompetitors($this->lastStep);
        if ($todoCompetitors->count() === 0) {
            return $this->getNextStep();
        }
        return $this->lastStep;
    }

    /**
     * @return Competitor
     */
    private function getNextCompetitor(): Competitor
    {
        //TODO - check when both nulls
        $todoCompetitors = $this->getTodoCompetitors($this->currentStep);
        return $todoCompetitors->first();
    }

    /**
     * @return ?Collection
     */
    private function getCompetitors(): ?Collection
    {
        //TODO - assure we only get completely filled competitors - check out laravel model features
        return Competitor::where('workspace_id', $this->thread->workspace_id)->get();
    }

    /**
     * @param RuleStep $step
     * @return ?Collection
     */
    private function getDoneCompetitors(RuleStep $step): ?Collection
    {
        $stepRuns = $this->getStepRuns($step);
        return RunCompetitor::where('run_id', 'IN', $stepRuns)->get();
    }

    private function getStepRuns(RuleStep $step)
    {
        return ThreadRun::where('step_id', $step->id)->get();
    }

    /**
     * @param RuleStep $step
     * @return ?Collection
     */
    private function getTodoCompetitors(RuleStep $step): ?Collection
    {
        $allCompetitors = $this->getCompetitors();
        $doneCompetitors = $this->getDoneCompetitors($step);
        return $allCompetitors->diff($doneCompetitors);
    }

    /**
     * @param ?int $lastPosition
     * @return ?RuleStep
     */
    public function getNextStep(?int $lastPosition = null)
    {
        $lastPosition ??= $this->getLastPosition();
        $nextStep = $this->getNextStepByPosition($lastPosition);
        if ($nextStep?->rule_sub_type->name === 'COMPETITORS' && $this->getCompetitors()->count() === 0) {
            $lastPosition++;
            return $this->getNextStep($lastPosition);
        } else {
            return $nextStep;
        }
    }

    /**
     * @param int $lastPosition
     * @return ?RuleStep
     */
    private function getNextStepByPosition(int $lastPosition): ?RuleStep
    {
        return RuleStep::where(
            [
                ['rule_id', $this->thread->rule_id],
                ['position', '>', $lastPosition],
            ]
        )->oldest('position')->first();
    }

    /**
     * @return int
     */
    private function getLastPosition(): int
    {
        return (int) ($this->lastStep?->rule->position ?? 0);
    }

    /**
     * @return string
     */
    private function getMessage(): string
    {
        $replacements = $this->getReplacements();

        return $this->parseContent($this->currentStep->message, $replacements);
    }

    /**
     * @return array
     */
    private function getReplacements(): array
    {
        $briefings = $this->getBriefingReplacements();
        $competitors = $this->currentStep->rule_sub_type->name === 'COMPETITORS' ? $this->getCompetitorReplacements() : [];
        $strategy = $this->getStrategyReplacements();

        return array_merge($briefings, $competitors, $strategy);
    }

    /**
     * @return array
     */
    private function getBriefingReplacements(): array
    {
        $briefing = Briefing::where(['workspace_id' => $this->thread->workspace_id])->latest()->first()?->content;
        $briefing = $this->translateFieldOptions($briefing, 'BRIEFINGS');

        return Arr::prependKeysWith($briefing, 'briefings.');
    }

    /**
     * @return array
     */
    private function getCompetitorReplacements(): array
    {
        $competitor = $this->getNextCompetitor()->content;
        $competitor = $this->translateFieldOptions($competitor, 'COMPETITORS');

        return Arr::prependKeysWith($competitor, 'competitors.');
    }

    /**
     * @return array
     */
    private function getStrategyReplacements(): array
    {
        $strategy = Strategy::where(['workspace_id' => $this->thread->workspace_id])->latest()->first()?->content;

        return $strategy ? Arr::prependKeysWith(
            Strategy::where(['workspace_id' => $this->thread->workspace_id])->latest()->first()?->content,
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
            'version_id' => $this->thread->rule->version_id,
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

}
