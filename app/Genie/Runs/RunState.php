<?php

namespace App\Genie\Runs;

use App\Enums\Modality;
use App\Enums\RunStatus;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\RuleStep;

/**
 * The single owner of run and step status.
 *
 * Each transition is one named event, and the run's status is derived from its steps rather than
 * copied from the newest one. A run type whose result has a status of its own — a strategy — hooks
 * the events below to keep it in step; one that has none — ideas — uses this class as it is.
 */
class RunState
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

        $this->onStarting($run);
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
     * The row is written anyway: a run that omits a step should say so rather than leave a hole
     * between positions, and it is what stops the runner from weighing the same step again.
     */
    public function skipped(AiRun $run, RuleStep $step): void
    {
        $run->steps()->updateOrCreate(
            ['step_id' => $step->id],
            [
                'position' => $step->position,
                'modality' => $step->modality ?? Modality::TEXT,
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

        $this->onResumed($runStep->run);
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

        $this->onFinished($run);
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

        $this->onFailed($runStep->run);
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
            $this->onPendingReview($run);
        }
    }

    protected function onStarting(AiRun $run): void {}

    protected function onResumed(AiRun $run): void {}

    protected function onFinished(AiRun $run): void {}

    protected function onFailed(AiRun $run): void {}

    protected function onPendingReview(AiRun $run): void {}
}
