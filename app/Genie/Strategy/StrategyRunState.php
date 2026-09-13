<?php

namespace App\Genie\Strategy;

use App\Enums\StrategyStatus;
use App\Genie\Runs\RunState;
use App\Models\AiRun;

/**
 * Run state for a strategy run: the run and step transitions of RunState, with the strategy's
 * own status kept in step with them.
 *
 * Previously this was spread across GenieStateRuns, GenieStateRunResponses, GenieStateStrategies
 * and inline branching in two jobs, with three consequences worth naming:
 *
 *  - the run's status was a copy of the last response's, so a run read COMPLETE between steps;
 *  - the strategy's end state was set from two places with different arguments;
 *  - the failure path passed a GenieData where a Strategy was type-hinted, so a failing strategy
 *    run threw inside its own failure handler and never recorded the failure.
 */
class StrategyRunState extends RunState
{
    protected function onStarting(AiRun $run): void
    {
        $run->strategy?->update(['status' => StrategyStatus::RUNNING]);
    }

    protected function onResumed(AiRun $run): void
    {
        $run->strategy?->update(['status' => StrategyStatus::REVIEWED]);
    }

    protected function onFinished(AiRun $run): void
    {
        $run->strategy?->update(['status' => StrategyStatus::PENDING_APPROVAL]);
    }

    protected function onFailed(AiRun $run): void
    {
        $run->strategy?->update(['status' => StrategyStatus::ERROR]);
    }

    protected function onPendingReview(AiRun $run): void
    {
        $run->strategy?->update(['status' => StrategyStatus::PENDING_REVIEW]);
    }
}
