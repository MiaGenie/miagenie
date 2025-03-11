<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;

trait WithInputOptions
{
    use WithTitle;

    /**
     * @param string $value
     * @param bool $first
     * @return Collection
     */
    public static function withInputOptions(string $value = '', bool $first = false): Collection
    {
        $withInputOptions = collect(self::cases())
            ->map(function ($enum) use ($value) {
                return collect([
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'title' => $enum->title(),
                    'hasLength' => $enum->hasLength(),
                    'hasStep' => $enum->hasStep(),
                    'hasValues' => $enum->hasValues(),
                    'isIdentifier' => $enum->isIdentifier()
                ]);
            })->filter(function ($item) use ($value) {
                return ($value === '' || (int) $value === $item['value']);
            });

        return $first ? $withInputOptions->first() : $withInputOptions;
    }

    /**
     * @return bool
     */
    abstract public function hasLength(): bool;

    /**
     * @return bool
     */
    abstract public function hasStep(): bool;

    /**
     * @return bool
     */
    abstract public function hasValues(): bool;

    /**
     * @return bool
     */
    abstract public function isIdentifier(): bool;

}

