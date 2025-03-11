<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;

trait WithGroupOptions
{
    use WithTitle;

    /**
     * @param string $value
     * @param bool $first
     * @return Collection
     */
    public static function withGroupOptions(string $value = '', bool $first = false): Collection
    {
        $withGroupOptions = collect(self::cases())
            ->map(function ($enum) use ($value) {
                return collect([
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'title' => $enum->title(),
                    'hasIdentifier' => $enum->hasIdentifier()
                ]);
            })->filter(function ($item) use ($value) {
                return ($value === '' || (int) $value === $item['value']);
            });

        return $first ? $withGroupOptions->first() : $withGroupOptions;
    }

    /**
     * @return bool
     */
    abstract public function hasIdentifier(): bool;
}

