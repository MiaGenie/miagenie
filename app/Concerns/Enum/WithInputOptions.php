<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;

trait WithInputOptions
{
    use WithTitle;

    public static function withInputOptions(string $value = '', bool $first = false): Collection
    {
        $withInputOptions = collect(self::cases())
            ->map(function ($enum) {
                return collect([
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'title' => $enum->title(),
                    'hasLength' => $enum->hasLength(),
                    'hasStep' => $enum->hasStep(),
                    'hasValues' => $enum->hasValues(),
                ]);
            })->filter(function ($item) use ($value) {
                return $value === '' || (int) $value === $item['value'];
            });

        return $first ? $withInputOptions->first() : $withInputOptions;
    }

    abstract public function hasLength(): bool;

    abstract public function hasStep(): bool;

    abstract public function hasValues(): bool;
}
