<?php

namespace App\Genie\Runs;

use App\Enums\RuleType;
use App\Genie\Ideas\IdeasRunner;
use App\Genie\Posts\PostsRunner;
use App\Genie\Posts\PostsRunState;
use App\Genie\Strategy\StrategyRunner;
use App\Genie\Strategy\StrategyRunState;

/**
 * Which runner drives a rule type.
 *
 * The one place a rule type is mapped to its machinery, so the job and the admin's resume can
 * stay agnostic. A new rule type is one more arm in each match.
 */
final class Runners
{
    public static function for(RuleType $type): Runner
    {
        return match ($type) {
            RuleType::STRATEGY => app(StrategyRunner::class),
            RuleType::IDEAS => app(IdeasRunner::class),
            RuleType::POSTS => app(PostsRunner::class),
        };
    }

    public static function stateFor(RuleType $type): RunState
    {
        return match ($type) {
            RuleType::STRATEGY => app(StrategyRunState::class),
            RuleType::IDEAS => app(RunState::class),
            RuleType::POSTS => app(PostsRunState::class),
        };
    }

    public static function supports(RuleType $type): bool
    {
        return in_array($type, RuleType::cases(), true);
    }
}
