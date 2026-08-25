<?php

namespace App\Genie\Data;

use App\Concerns\GenieParser;
use App\Contracts\GenieRunDataContract;
use App\Enums\GenieSyncAction;
use App\Models\RuleStep;
use App\Models\Run;
use App\Models\RunResponse;
use App\Models\VersionField;
use App\Models\VersionFieldOption;
use Inovector\Mixpost\Facades\WorkspaceManager;

class GenieRunData implements GenieRunDataContract
{
    use GenieParser;

    protected const TYPE = 'RUN';

    private Run $run;

    private GenieSyncAction $action;

    private ?RuleStep $lastStep;

    private ?RuleStep $nextStep;

    private array $channelFields;

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

    public function getAction(): GenieSyncAction
    {
        return $this->action;
    }

    public function getModel(): Run
    {
        return $this->run;
    }

    private function lastStep(): ?RuleStep
    {
        if (isset($this->lastStep)) {
            return $this->lastStep;
        }

        $lastRunResponse = $this->getLastRunResponse();
        $this->lastStep = $lastRunResponse?->id ? RuleStep::find($lastRunResponse?->step_id) : null;

        return $this->lastStep;
    }

    public function nextStep(?RuleStep $lastStep = null): ?RuleStep
    {
        if (isset($this->nextStep)) {
            return $this->nextStep;
        }

        $lastRunResponse = $this->getLastRunResponse();

        if ($lastRunResponse && ($lastRunResponse?->status->isError() || ! $lastRunResponse?->status->isComplete())
            && ! $lastRunResponse?->status->requiresUpdate()
        ) {
            $this->nextStep = $this->lastStep();
        } else {
            $this->nextStep = $this->getNextStep();
        }

        return $this->nextStep;
    }

    private function getLastRunResponse(): ?RunResponse
    {
        return RunResponse::where(['run_id' => $this->run->id])->latest('id')->first();
    }

    private function getNextStep(?int $lastPosition = null): ?RuleStep
    {
        $lastPosition ??= $this->getLastPosition();
        $nextStep = $this->getNextStepByPosition($lastPosition);
        if ($nextStep?->rule_sub_type->name === 'CHANNELS') {
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

    private function getNextStepByPosition(int $lastPosition): ?RuleStep
    {
        return RuleStep::where(
            [
                ['rule_id', $this->run->rule_id],
                ['position', '>', $lastPosition],
            ]
        )->oldest('position')->first();
    }

    private function getLastPosition(): int
    {
        return $this->lastStep?->position ?? 0;
    }

    private function isValidChannelStep(?RuleStep $nextStep): bool
    {
        $fieldName = VersionField::findOrFail($nextStep->depends_on_field)->code_name;
        $fieldOption = VersionFieldOption::findOrFail($nextStep->depends_on_option)->code_name;
        $fieldOptions = $this->run->strategy->content[$fieldName];

        return in_array($fieldOption, $fieldOptions);

    }
}
