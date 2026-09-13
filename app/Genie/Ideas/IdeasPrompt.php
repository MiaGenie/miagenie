<?php

namespace App\Genie\Ideas;

use App\Genie\Runs\RunPrompt;
use App\Models\AiRun;
use App\Models\RuleStep;

/**
 * The message for one step of an ideas run: the briefing and the strategy every run can read,
 * plus what this run was asked for — see IdeasContext.
 */
class IdeasPrompt extends RunPrompt
{
    public function __construct(
        AiRun $run,
        RuleStep $step,
        string $locale,
        IdeasRequest $request,
        int $share,
    ) {
        parent::__construct($run, $step, $locale, new IdeasContext($run, $step, $request, $share, $locale));
    }
}
