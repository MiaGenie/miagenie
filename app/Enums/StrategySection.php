<?php

namespace App\Enums;

use App\Concerns\Enum\WithTitle;

/**
 * How a strategy field's section is presented on the strategy pages, stored in the field's
 * `block` column. No value is the standard card.
 */
enum StrategySection: string
{
    use WithTitle;

    case CARD = 'card';
    case FEATURE = 'feature';
    case COMPACT = 'compact';

    public function title(): string
    {
        return __('genie.strategy_section_'.$this->value);
    }
}
