<?php

namespace App\Enums;

use App\Concerns\Enum\FromName;
use App\Concerns\Enum\WithTitle;
use Illuminate\Support\Collection;

enum SubFieldType: int
{
    use FromName;
    use WithTitle;

    case OBJECT = 1;
    case ARRAY = 2;
    case STRING = 3;
    case BOOLEAN = 4;

    public function title(): string
    {
        return match ($this) {
            self::OBJECT => 'object',
            self::ARRAY => 'array',
            self::STRING => 'string',
            self::BOOLEAN => 'boolean'
        };
    }

    /**
     * Whether the type nests other sub-fields beneath it.
     */
    public function hasChildren(): bool
    {
        return match ($this) {
            self::OBJECT => true,
            self::ARRAY => true,
            self::STRING => false,
            self::BOOLEAN => false
        };
    }

    /**
     * Whether the type accepts min_length / max_length constraints.
     *
     * On an array they constrain each item, which only makes sense while the items are
     * strings — an array with children describes objects instead.
     */
    public function hasLength(): bool
    {
        return match ($this) {
            self::OBJECT => false,
            self::ARRAY => true,
            self::STRING => true,
            self::BOOLEAN => false
        };
    }

    /**
     * Whether the type accepts min_items / max_items constraints.
     */
    public function hasItems(): bool
    {
        return match ($this) {
            self::OBJECT => false,
            self::ARRAY => true,
            self::STRING => false,
            self::BOOLEAN => false
        };
    }

    /**
     * Whether the type accepts a fixed list of allowed values.
     *
     * As with length, an array applies them to each of its string items.
     */
    public function hasEnum(): bool
    {
        return match ($this) {
            self::OBJECT => false,
            self::ARRAY => true,
            self::STRING => true,
            self::BOOLEAN => false
        };
    }

    /**
     * The cases decorated with their capability flags, for the admin form.
     */
    public static function withSubFieldOptions(): Collection
    {
        return collect(self::cases())
            ->map(function (self $enum) {
                return collect([
                    'name' => $enum->name,
                    'value' => $enum->value,
                    'title' => $enum->title(),
                    'hasChildren' => $enum->hasChildren(),
                    'hasLength' => $enum->hasLength(),
                    'hasItems' => $enum->hasItems(),
                    'hasEnum' => $enum->hasEnum(),
                ]);
            });
    }
}
