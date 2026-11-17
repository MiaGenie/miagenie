<?php

namespace App\Genie\Strategy;

use RuntimeException;

/**
 * A reason a workspace's strategy cannot be regenerated right now, named by the lang key the
 * admin sees.
 */
class StrategyRegenerationException extends RuntimeException
{
    public function __construct(public readonly string $key)
    {
        parent::__construct((string) __('genie.'.$key));
    }

    public static function generating(): self
    {
        return new self('strategy_regeneration_generating');
    }

    public static function noBriefing(): self
    {
        return new self('strategy_regeneration_no_briefing');
    }
}
