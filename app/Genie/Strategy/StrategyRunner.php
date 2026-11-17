<?php

namespace App\Genie\Strategy;

use App\Ai\StepAgentFactory;
use App\Genie\Runs\Runner;
use App\Genie\Runs\RunPrompt;
use App\Genie\Runs\RunState;
use App\Genie\Runs\StepGate;
use App\Genie\Runs\StepOutput;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\Strategy;

/**
 * Runs one step of a strategy run.
 *
 * A strategy step's schema is compiled from the version's sub-fields by the agent factory, its
 * output lands on the strategy, and a CHANNELS step only runs when the strategy chose its channel.
 */
class StrategyRunner extends Runner
{
    public function __construct(
        StepAgentFactory $agents = new StepAgentFactory,
        RunState $state = new StrategyRunState,
        StepOutput $output = new StrategyOutput,
        StepGate $gate = new StrategyChannelGate,
    ) {
        parent::__construct($agents, $state, $output, $gate);
    }

    /**
     * A strategy run is superseded once an admin has asked for a newer strategy: the strategy pages
     * read only the latest one, so finishing this run would bring back a strategy nobody sees. Only
     * a settled strategy can be regenerated, so this catches a failed run's pending retry.
     */
    public function isSuperseded(AiRun $run): bool
    {
        if (! $run->strategy_id) {
            return false;
        }

        return Strategy::byWorkspace($run->workspace_id)
            ->where('id', '>', $run->strategy_id)
            ->exists();
    }

    protected function prompt(AiRunStep $runStep, string $locale): RunPrompt
    {
        return new StrategyPrompt($runStep->run, $runStep->step, $locale);
    }

    protected function label(): string
    {
        return 'strategy';
    }
}
