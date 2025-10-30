<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;

trait WithTitle
{
    /**
     * @param string $name
     * @param bool $first
     * @return Collection
     */
    public static function withTitle(string $name = '', string $prefix = ''): Collection
    {
        $withTitle = collect(self::cases())
            ->map(function ($enum) use ($name) {
                return collect([
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'title' => $enum->title(),
                ]);
            })->filter(function ($item) use ($name) {
                return ($name === '' || $name === $item['name']);
            })->filter(function ($item) use ($prefix) {
                return ($prefix === '' || str_starts_with($item['value'], $prefix));
            });

        return $withTitle;
    }

    /**
     * @return string
     */
    abstract public function title(): string;
}

