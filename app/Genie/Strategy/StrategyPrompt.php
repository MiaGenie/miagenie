<?php

namespace App\Genie\Strategy;

use App\Genie\Runs\RunPrompt;

/**
 * The message for one step of a strategy run.
 *
 * A strategy step reads nothing beyond the briefing and the strategy written so far, both of which
 * every run type gets from RunPrompt, so there is nothing to add here.
 */
class StrategyPrompt extends RunPrompt {}
