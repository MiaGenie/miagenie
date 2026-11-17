<?php

namespace App\Enums;

/**
 * The groups a strategy style token belongs to. A node carries at most one token of each, so a
 * tone, an emphasis and an animation combine but two tones never fight over the same element.
 */
enum StrategyStyleCategory: string
{
    case TONE = 'tone';
    case EMPHASIS = 'emphasis';
    case ANIMATION = 'animation';

    public function title(): string
    {
        return __('genie.strategy_style_category_'.$this->value);
    }
}
