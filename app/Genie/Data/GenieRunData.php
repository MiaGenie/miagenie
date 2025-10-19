<?php

namespace App\Genie\Data;

use App\Concerns\GenieParser;
use App\Enums\GenieSyncAction;
use App\Models\Competitor;
use App\Models\RuleStep;
use App\Models\RunCompetitor;
use App\Models\Run;
use App\Models\RunResponse;
use App\Models\Strategy;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Illuminate\Support\Collection;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieRunData
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
        array $channelFields = []
    ) {
        $this->run = $model;
        $this->action = $action;
        WorkspaceManager::setCurrent($this->run->workspace);
        $this->lastStep = $this->lastStep();
        $this->nextStep = $this->nextStep($this->lastStep);
        $this->channelFields = $channelFields;
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
    public function lastStep(): ?RuleStep
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
                null, 'BRIEFINGS', 'BRIEFINGS_MULTIPLE', 'CHANNELS' => $this->getNextStep(),
                'COMPETITORS' => $this->getNextStepCompetitor()
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
    private function getNextStepCompetitor(): ?RuleStep
    {
        $todoCompetitors = $this->getTodoCompetitors($this->lastStep);
        if ($todoCompetitors->count() === 0) {
            return $this->getNextStep();
        }
        return $this->lastStep;
    }

    /**
     * @return ?Competitor
     */
    public function getNextCompetitor(): ?Competitor
    {
        //TODO - check when both nulls
        $todoCompetitors = $this->getTodoCompetitors($this->nextStep);
        return Competitor::find($todoCompetitors->first());
    }

    /**
     * @return Collection
     */
    private function getCompetitorIds(): Collection
    {
        //TODO - assure we only get completely filled competitors - check out laravel model features
        return Competitor::where('workspace_id', $this->run->workspace_id)->pluck('id');
    }

    /**
     * @param RuleStep $step
     * @return Collection
     */
    private function getDoneCompetitors(RuleStep $step): Collection
    {
        $stepRunResponses = $this->run->runResponses->where('step_id', $step->id)->pluck('id')->toArray();
        $stepRunCompetitors = RunCompetitor::whereIn('run_response_id', $stepRunResponses)->pluck('competitor_id');
        return Competitor::whereIn('id', $stepRunCompetitors)->pluck('id');
    }

    /**
     * @param RuleStep $step
     * @return ?Collection
     */
    private function getTodoCompetitors(RuleStep $step): ?Collection
    {
        $allCompetitors = $this->getCompetitorIds();
        $doneCompetitors = $this->getDoneCompetitors($step);
        return $allCompetitors->diff($doneCompetitors);
    }

    /**
     * @param ?int $lastPosition
     * @return ?RuleStep
     */
    private function getNextStep(?int $lastPosition = null): ?RuleStep
    {
        $lastPosition ??= $this->getLastPosition();
        $nextStep = $this->getNextStepByPosition($lastPosition);
        if ($nextStep?->rule_sub_type->name === 'COMPETITORS' && $this->getCompetitorIds()->count() === 0) {
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
