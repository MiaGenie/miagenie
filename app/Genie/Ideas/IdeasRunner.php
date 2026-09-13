<?php

namespace App\Genie\Ideas;

use App\Ai\StepAgentFactory;
use App\Genie\Runs\PostFormatGate;
use App\Genie\Runs\Runner;
use App\Genie\Runs\RunPrompt;
use App\Genie\Runs\RunState;
use App\Genie\Runs\SchemaDefinition;
use App\Genie\Runs\StepOutput;
use App\Models\AiRunStep;

/**
 * Runs one step of an ideas run.
 *
 * The context step primes the conversation; each generator step that matches the requested
 * format then produces its share of the ideas, against a schema narrowed to what was asked for.
 */
class IdeasRunner extends Runner
{
    protected PostFormatGate $formats;

    public function __construct(
        StepAgentFactory $agents = new StepAgentFactory,
        RunState $state = new RunState,
        StepOutput $output = new IdeasOutput,
        PostFormatGate $gate = new PostFormatGate,
    ) {
        parent::__construct($agents, $state, $output, $gate);

        $this->formats = $gate;
    }

    protected function prompt(AiRunStep $runStep, string $locale): RunPrompt
    {
        return new IdeasPrompt(
            $runStep->run,
            $runStep->step,
            $locale,
            IdeasRequest::fromRun($runStep->run),
            $this->share($runStep),
        );
    }

    protected function schema(AiRunStep $runStep, string $locale): ?SchemaDefinition
    {
        if (! $runStep->step?->rule_sub_type?->isGenerator()) {
            return null;
        }

        return IdeaSchema::forRun(
            $runStep->run,
            IdeasRequest::fromRun($runStep->run),
            $this->share($runStep),
            $locale,
        );
    }

    protected function label(): string
    {
        return 'ideas';
    }

    /**
     * This step's share of the total: the generators are ordered as the rule orders them, and the
     * total is split between them by that position.
     */
    protected function share(AiRunStep $runStep): int
    {
        $generators = $this->formats->generators($runStep->run);

        $index = $generators->search(fn ($step) => $step->id === $runStep->step_id);

        if ($index === false) {
            return 0;
        }

        return IdeasRequest::fromRun($runStep->run)->shareFor($index, $generators->count());
    }
}
