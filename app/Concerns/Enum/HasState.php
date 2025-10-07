<?php

namespace App\Concerns\Enum;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasState
{
    /**
     * @param string $name
     * @param bool $first
     * @return Collection
     */
    public static function withState(string $name = '', bool $addTitle = false): Collection
    {
        $withState = collect(self::cases())
            ->map(function ($enum) use ($name, $addTitle) {
                $collection = [
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'isError' => $enum->isError(),
                    'requiresUpdate' => $enum->requiresUpdate(),
                    'isComplete' => $enum->isComplete(),
                ];
                if ($addTitle) {
                    $collection['title'] = $enum->title() ?? '';
                }
                return collect($collection);
            })->filter(function ($item) use ($name) {
                return ($name === '' || $name === $item['name']);
            });

        return $withState;
    }

    /**
     * @return bool
     */
    abstract public function isError(): bool;

    /**
     * @return bool
     */
    abstract public function requiresUpdate(): bool;

    /**
     * @return bool
     */
    abstract public function isComplete(): bool;
}

