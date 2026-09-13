<?php

namespace Tests\Unit;

use App\Enums\RuleType;
use App\Genie\Ideas\IdeasRunner;
use App\Genie\Runs\Runners;
use App\Genie\Runs\RunState;
use App\Genie\Strategy\StrategyRunner;
use App\Genie\Strategy\StrategyRunState;
use RuntimeException;
use Tests\TestCase;

class RunnersTest extends TestCase
{
    public function test_a_strategy_run_is_driven_by_the_strategy_runner(): void
    {
        $this->assertInstanceOf(StrategyRunner::class, Runners::for(RuleType::STRATEGY));
        $this->assertInstanceOf(StrategyRunState::class, Runners::stateFor(RuleType::STRATEGY));
    }

    public function test_an_ideas_run_is_driven_by_the_ideas_runner(): void
    {
        $this->assertInstanceOf(IdeasRunner::class, Runners::for(RuleType::IDEAS));
        $this->assertInstanceOf(RunState::class, Runners::stateFor(RuleType::IDEAS));
        $this->assertNotInstanceOf(StrategyRunState::class, Runners::stateFor(RuleType::IDEAS));
    }

    public function test_a_rule_type_without_a_runner_is_refused(): void
    {
        $this->assertFalse(Runners::supports(RuleType::DRAFTS));

        $this->expectException(RuntimeException::class);

        Runners::for(RuleType::DRAFTS);
    }
}
