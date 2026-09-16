<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;

trait WithGroupOptions
{
    use WithTitle;

    public static function withGroupOptions(string $value = '', bool $first = false): Collection
    {
        $withGroupOptions = collect(self::cases())
            ->map(function ($enum) {
                return collect([
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'title' => $enum->title(),
                ]);
            })->filter(function ($item) use ($value) {
                return $value === '' || (int) $value === $item['value'];
            });

        return $first ? $withGroupOptions->first() : $withGroupOptions;
    }
}
