<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;

trait WithFieldOptions
{
    use WithTitle;

    /**
     * @param string $value
     * @param bool $first
     * @return Collection
     */
    public static function withFieldOptions(string $value = '', bool $first = false): Collection
    {
        $withFieldOptions = collect(self::cases())
            ->map(function ($enum) use ($value) {
                return collect([
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'title' => $enum->title(),
                    'hasGroups' => $enum->hasGroups(),
                    'hasLength' => $enum->hasLength(),
                    'hasMulti' => $enum->hasMulti(),
                    'hasOptions' => $enum->hasOptions(),
                    'hasRows' => $enum->hasRows(),
                    'isInput' => $enum->isInput(),
                    'isFile' => $enum->isFile(),
                    'isOutput' => $enum->isOutput(),
                    'isRadio' => $enum->isRadio(),
                ]);
            })->filter(function ($item) use ($value) {
                return ($value === '' || (int) $value === $item['value']);
            });

        return $first ? $withFieldOptions->first() : $withFieldOptions;
    }

    /**
     * @return bool
     */
    abstract public function hasGroups(): bool;

    /**
     * @return bool
     */
    abstract public function hasLength(): bool;

    /**
     * @return bool
     */
    abstract public function hasMulti(): bool;

    /**
     * @return bool
     */
    abstract public function hasOptions(): bool;

    /**
     * @return bool
     */
    abstract public function hasRows(): bool;

    /**
     * @return bool
     */
    abstract public function isInput(): bool;

    /**
     * @return bool
     */
    abstract public function isOutput(): bool;

    /**
     * @return bool
     */
    abstract public function isFile(): bool;

    /**
     * @return bool
     */
    abstract public function isRadio(): bool;

}

