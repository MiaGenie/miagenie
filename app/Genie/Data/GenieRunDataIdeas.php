<?php

namespace App\Genie\Data;

use App\Concerns\GenieParser;
use App\Contracts\GenieRunDataContract;
use App\Enums\GenieSyncAction;
use App\Enums\RunStatus;
use App\Models\RuleStep;
use App\Models\Run;
use App\Models\RunResponse;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieRunDataIdeas implements GenieRunDataContract
{
    use GenieParser;

    protected const TYPE = 'RUN';

    /**
     * @var Run
     */
    private Run $run;

    /**
     * @var GenieSyncAction
     */
    private GenieSyncAction $action;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $lastStep;

    /**
     * @var ?RuleStep
     */
    private ?RuleStep $nextStep;

    /**
     * @var array
     */
    private array $channelFields;

    /**
     * @param Run $model
     */
    public function __construct(
        Run $model,
        GenieSyncAction $action,
    ) {
        $this->run = $model;
        $this->action = $action;
        WorkspaceManager::setCurrent($this->run->workspace);
        $this->lastStep = $this->lastStep();
        $this->nextStep = $this->nextStep($this->lastStep);
    }

    /**
     * @return GenieSyncAction
     */
    public function getAction(): GenieSyncAction
    {
        return $this->action;
    }

    /**
     * @return Run
     */
    public function getModel(): Run
    {
        return $this->run;
    }

    /**
     * @return ?RuleStep
     */
    private function lastStep(): ?RuleStep
    {
        if (isset($this->lastStep)) {
            return $this->lastStep;
        }

        $lastRunResponse = $this->getLastRunResponse();
        $this->lastStep = $lastRunResponse?->id ? RuleStep::find($lastRunResponse?->step_id) : null;

        return $this->lastStep;
    }

    /**
     * @param ?RuleStep $lastStep
     * @return ?RuleStep
     */
    public function nextStep(?RuleStep $lastStep = null): ?RuleStep
    {
        if (isset($this->nextStep)) {
            return $this->nextStep;
        }

        $lastRunResponse = $this->getLastRunResponse();

        if ($lastRunResponse && ($lastRunResponse?->status->isError() || !$lastRunResponse?->status->isComplete())
            && !$lastRunResponse?->status->requiresUpdate()
        ) {
            $this->nextStep = $this->lastStep();
        } else {
            $this->nextStep = match ($lastStep?->rule_sub_type->name) {
                null, 'IDEAS_INITIAL', 'IDEAS_SIMPLE' => $this->getNextStep(),
                'IDEAS_MULTIPLE' => $this->getNextStepIterator()
            };
        }

        return $this->nextStep;
    }

    /**
     * @return ?RunResponse
     */
    private function getLastRunResponse(): ?RunResponse
    {
        return RunResponse::where(['run_id' => $this->run->id])->latest('id')->first();
    }

    /**
     * @return ?RuleStep
     */
    private function getNextStepIterator(): ?RuleStep
    {
        $todoIterators = $this->getTodoIterators($this->lastStep);
        if ($todoIterators->count() === 0) {
            return $this->getNextStep();
        }
        return $this->lastStep;
    }

    /**
     * @return int
     */
    public function getNextIteratorId(): int
    {
        //TODO - check when both nulls
        $todoIterators = $this->getTodoIterators($this->nextStep);
        return (int) $todoIterators->first();
    }

    /**
     * @return Collection
     */
    private function getIterators(RuleStep $step): Collection
    {
        $fieldValues = collect($this->run->runStrategy->strategy->content[$step->dependsOnField->code_name] ?? []);
        $fieldValues = $fieldValues->map(function ($value, $index) {
            $value['id'] = $index;
            return $value;
        });
        return $fieldValues;
    }

    /**
     * @param RuleStep $step
     * @return Collection
     */
    private function getDoneIterators(RuleStep $step): Collection
    {
        $stepRunIterators = $this->run->runFieldIterators;
        return $stepRunIterators;
    }

    /**
     * @param RuleStep $step
     * @return ?Collection
     */
    private function getTodoIterators(RuleStep $step): ?Collection
    {
        $allIterators = $this->getIterators($step);
        $doneIterators = $this->getDoneIterators($step);
        return $allIterators->pluck('id')->diff($doneIterators->pluck('field_index'));
    }

    /**
     * @param ?int $lastPosition
     * @return ?RuleStep
     */
    private function getNextStep(?int $lastPosition = null): ?RuleStep
    {
        $lastPosition ??= $this->getLastPosition();
        $nextStep = $this->getNextStepByPosition($lastPosition);
        if ($nextStep?->rule_sub_type->name ===  'IDEAS_INITIAL' && $nextStep?->link_upstream) {
            $initialResponse = RunResponse::with(['run' => function ($query) {$query->where('workspace_id', $this->run->workspace->id);}])
                ->where(['step_id' => $nextStep->id, 'status' => RunStatus::COMPLETE])
                ->first();
            if ($initialResponse) {
                $lastPosition = $nextStep->position;
                return $this->getNextStep($lastPosition);
            }
            return $nextStep;
        } elseif ($nextStep?->rule_sub_type->name === 'IDEAS_MULTIPLE' && $this->getIterators($nextStep)->count() === 0) {
            $lastPosition = $nextStep->position;
            return $this->getNextStep($lastPosition);
        } elseif ($nextStep?->rule_sub_type->name === 'CHANNELS') {
            if ($this->isValidChannelStep($nextStep)) {
                return $nextStep;
            } else {
                $lastPosition = $nextStep->position;
                return $this->getNextStep($lastPosition);
            }
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
                ['rule_id', $this->run->rule_id],
                ['position', '>', $lastPosition],
            ]
        )->oldest('position')->first();
    }

    /**
     * @return int
     */
    private function getLastPosition(): int
    {
        return $this->lastStep?->position ?? 0;
    }

    /**
     * @param RuleStep|null $nextStep
     * @return bool
     */
    private function isValidChannelStep(?RuleStep $nextStep): bool
    {
        $fieldName = VersionField::findOrFail($nextStep->depends_on_field)->code_name;
        $fieldOption = VersionFieldOption::findOrFail($nextStep->depends_on_option)->code_name;
        $fieldOptions = $this->run->strategy->content[$fieldName];

        return $fieldOptions[$fieldOption] ?? false;

    }
}
