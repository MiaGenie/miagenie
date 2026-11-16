<?php

namespace App\Enums;

/**
 * What an admin asks for when a workspace's strategy is regenerated.
 *
 * Every mode keeps the previous strategy and lets the customer start a new generation from the
 * strategy page. NEW_BRIEFING also opens an empty briefing the customer has to fill in first;
 * CLONED_BRIEFING opens one pre-filled with the current briefing's answers, still to be finished
 * in the wizard.
 */
enum StrategyRegenerationMode: string
{
    case STRATEGY = 'strategy';
    case NEW_BRIEFING = 'new_briefing';
    case CLONED_BRIEFING = 'cloned_briefing';

    public function createsBriefing(): bool
    {
        return $this !== self::STRATEGY;
    }

    public function clonesBriefing(): bool
    {
        return $this === self::CLONED_BRIEFING;
    }

    /**
     * The lang key of the message an admin sees once the mode has been applied.
     */
    public function successKey(): string
    {
        return match ($this) {
            self::STRATEGY => 'genie.strategy_regeneration_requested',
            self::NEW_BRIEFING => 'genie.strategy_regeneration_new_briefing_requested',
            self::CLONED_BRIEFING => 'genie.strategy_regeneration_cloned_briefing_requested',
        };
    }
}
