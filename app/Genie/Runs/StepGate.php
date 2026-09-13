<?php

namespace App\Genie\Runs;

use App\Models\AiRun;
use App\Models\RuleStep;

/**
 * Decides whether a rule step applies to a run.
 */
interface StepGate
{
    public function passes(AiRun $run, RuleStep $step): bool;
}
