<?php

namespace Tests\Unit;

use App\Enums\RuleType;
use App\Genie\Ideas\IdeasRunner;
use App\Genie\Posts\PostsRunner;
use App\Genie\Posts\PostsRunState;
use App\Genie\Runs\Runners;
use App\Genie\Runs\RunState;
use App\Genie\Strategy\StrategyRunner;
use App\Genie\Strategy\StrategyRunState;
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

    public function test_a_posts_run_is_driven_by_the_posts_runner(): void
    {
        $this->assertTrue(Runners::supports(RuleType::POSTS));
        $this->assertInstanceOf(PostsRunner::class, Runners::for(RuleType::POSTS));
        $this->assertInstanceOf(PostsRunState::class, Runners::stateFor(RuleType::POSTS));
    }
}
