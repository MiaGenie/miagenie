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
    public static function withTitle(string $name = '', bool $first = false): Collection
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
            });

        return $first ? $withTitle->first() : $withTitle;
    }

    /**
     * @return string
     */
    abstract public function title(): string;
}

