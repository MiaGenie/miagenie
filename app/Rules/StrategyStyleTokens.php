<?php

namespace App\Rules;

use App\Enums\StrategyStyle;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * A strategy `class` value: space separated `StrategyStyle` tokens, at most one of each category.
 */
class StrategyStyleTokens implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('genie.strategy_style_invalid')->translate();

            return;
        }

        $categories = [];

        foreach (StrategyStyle::tokens($value) as $token) {
            $style = StrategyStyle::tryFrom($token);

            if ($style === null) {
                $fail('genie.strategy_style_unknown')->translate(['token' => $token]);

                return;
            }

            $category = $style->category();

            if (isset($categories[$category->value])) {
                $fail('genie.strategy_style_one_per_category')->translate(['category' => $category->title()]);

                return;
            }

            $categories[$category->value] = true;
        }
    }
}
