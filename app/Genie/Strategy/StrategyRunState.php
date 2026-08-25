<?php

namespace App\Genie\Strategy;

use App\Enums\Modality;
use App\Enums\RunStatus;
use App\Enums\StrategyStatus;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\RuleStep;

/**
 * The single owner of run, step and strategy status.
 *
 * Previously this was spread across GenieStateRuns, GenieStateRunResponses, GenieStateStrategies
 * and inline branching in two jobs, with three consequences worth naming:
 *
 *  - the run's status was a copy of the last response's, so a run read COMPLETE between steps;
 *  - the strategy's end state was set from two places with different arguments;
 *  - the failure path passed a GenieData where a Strategy was type-hinted, so a failing strategy
 *    run threw inside its own failure handler and never recorded the failure.
 *
 * Here the run's status is derived from its steps, and each transition is one named event.
 */
class StrategyRunState
{
    /**
     * A step is about to be prompted.
     */
    public function starting(AiRunStep $runStep): void
    {
        $runStep->update(['status' => RunStatus::RUNNING]);

        $run = $runStep->run;

        $run->update([
            'status' => RunStatus::RUNNING,
            'started_at' => $run->started_at ?? now(),
        ]);

        $run->strategy?->update(['status' => StrategyStatus::RUNNING]);
    }

    /**
     * A step returned successfully. Whether the run continues is the caller's decision; this only
     * records where the step landed.
     */
    public function completed(AiRunStep $runStep): void
    {
        $runStep->update([
            'status' => $runStep->step?->requires_review
                ? RunStatus::PENDING_REVIEW
                : RunStatus::COMPLETE,
        ]);

        $this->syncRun($runStep->run);
    }

    /**
     * A step the rule passed over, because it did not apply to this run.
     *
     * The row is written anyway: a run that omits a channel should say so rather than leave a hole
     * between positions, and it is what stops the runner from weighing the same step again.
     */
    public function skipped(AiRun $run, RuleStep $step): void
    {
        $run->steps()->updateOrCreate(
            ['step_id' => $step->id],
            [
                'position' => $step->position,
                'modality' => Modality::TEXT,
                'status' => RunStatus::SKIPPED,
            ]
        );
    }

    /**
     * A reviewer released a gated step.
     */
    public function resumed(AiRunStep $runStep): void
    {
        $runStep->update(['status' => RunStatus::COMPLETE]);

        $runStep->run->update(['status' => RunStatus::RUNNING]);
        $runStep->run->strategy?->update(['status' => StrategyStatus::REVIEWED]);
    }

    /**
     * Every step has run and none is waiting.
     */
    public function finished(AiRun $run): void
    {
        $run->update([
            'status' => RunStatus::COMPLETE,
            'completed_at' => now(),
        ]);

        $run->strategy?->update(['status' => StrategyStatus::PENDING_APPROVAL]);
    }

    /**
     * The step could not be produced.
     */
    public function failed(AiRunStep $runStep, ?string $details = null): void
    {
        $runStep->update([
            'status' => RunStatus::ERROR,
            'error_details' => $details ? mb_substr($details, 0, 255) : $runStep->error_details,
        ]);

        $runStep->run->update(['status' => RunStatus::ERROR]);
        $runStep->run->strategy?->update(['status' => StrategyStatus::ERROR]);
    }

    /**
     * Derive the run's status from its steps rather than copying the newest one.
     */
    protected function syncRun(AiRun $run): void
    {
        $steps = $run->steps()->reorder()->get();

        $status = match (true) {
            $steps->contains(fn (AiRunStep $s) => $s->status === RunStatus::ERROR) => RunStatus::ERROR,
            $steps->contains(fn (AiRunStep $s) => $s->status === RunStatus::PENDING_REVIEW) => RunStatus::PENDING_REVIEW,
            default => RunStatus::RUNNING,
        };

        $run->update(['status' => $status]);

        if ($status === RunStatus::PENDING_REVIEW) {
            $run->strategy?->update(['status' => StrategyStatus::PENDING_REVIEW]);
        }
    }
}
