<?php

namespace App\Concerns\Requests;

use App\Enums\StrategySection;
use App\Enums\StrategyStyle;
use App\Enums\VersionGroupType;
use App\Rules\StrategyStyleTokens;
use Illuminate\Validation\Rule;

/**
 * A field's styling on the strategy pages: `block` picks how its section is presented and `class`
 * holds its style tokens. Only a strategy field is presented there, so any other stores neither,
 * whatever was sent. The group is read from the form, since an admin may move a field between
 * groups on the same save.
 */
trait ValidatesStrategyStyling
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function stylingRules(): array
    {
        return [
            'block' => ['nullable', Rule::enum(StrategySection::class)],
            'class' => ['nullable', 'max:255', new StrategyStyleTokens],
        ];
    }

    /**
     * @return array{block: string|null, class: string|null}
     */
    protected function stylingAttributes(): array
    {
        $supported = VersionGroupType::tryFrom((int) $this->input('group_type'))?->supportsStyling() ?? false;

        if (! $supported) {
            return ['block' => null, 'class' => null];
        }

        return [
            'block' => StrategySection::tryFrom((string) $this->input('block'))?->value,
            'class' => StrategyStyle::sanitize($this->input('class')),
        ];
    }
}
