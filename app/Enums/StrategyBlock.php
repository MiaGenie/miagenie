<?php

namespace App\Enums;

use Illuminate\Support\Collection;

/**
 * The layout a strategy sub-field's value is drawn with on the strategy pages, stored in the
 * sub-field's `block` column.
 *
 * No value lets the page infer the layout from the compiled schema, as it always has; a block
 * only overrides that choice. Each one fits a sub-field of a given type and shape, and a block
 * that does not fit is ignored rather than drawn wrong. A new layout is a case here plus its
 * component in `resources/js/Components/Genie/Strategy/Blocks`.
 */
enum StrategyBlock: string
{
    case STACK = 'stack';
    case CARDS = 'cards';
    case CHOICES = 'choices';
    case BULLETS = 'bullets';
    case NUMBERED_CARDS = 'numbered_cards';
    case CHECKBOX = 'checkbox';
    case TEXT = 'text';

    public function title(): string
    {
        return __('genie.strategy_block_'.$this->value);
    }

    public function type(): SubFieldType
    {
        return match ($this) {
            self::STACK, self::CARDS, self::CHOICES => SubFieldType::OBJECT,
            self::BULLETS, self::NUMBERED_CARDS => SubFieldType::ARRAY,
            self::CHECKBOX => SubFieldType::BOOLEAN,
            self::TEXT => SubFieldType::STRING,
        };
    }

    /**
     * Whether the block needs children (true), must have none (false) or takes either (null).
     *
     * An array without children holds strings and one with children holds objects, so the two
     * array layouts split on it.
     */
    public function requiresChildren(): ?bool
    {
        return match ($this) {
            self::CHOICES, self::NUMBERED_CARDS => true,
            self::BULLETS => false,
            self::STACK, self::CARDS, self::CHECKBOX, self::TEXT => null,
        };
    }

    /**
     * Whether every child must be a boolean, the shape a multiple choice compiles to.
     */
    public function requiresBooleanChildren(): bool
    {
        return $this === self::CHOICES;
    }

    /**
     * Whether the block can draw a sub-field of this type whose direct children are of these types.
     *
     * @param  array<int, SubFieldType>  $childTypes
     */
    public function fits(SubFieldType $type, array $childTypes): bool
    {
        if ($type !== $this->type()) {
            return false;
        }

        if ($this->requiresChildren() !== null && $this->requiresChildren() !== ($childTypes !== [])) {
            return false;
        }

        if ($this->requiresBooleanChildren()) {
            foreach ($childTypes as $childType) {
                if ($childType !== SubFieldType::BOOLEAN) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * The stored block when it is one that fits the sub-field, otherwise null.
     *
     * @param  array<int, SubFieldType>  $childTypes
     */
    public static function resolve(?string $block, SubFieldType $type, array $childTypes): ?self
    {
        $case = self::tryFrom((string) $block);

        return $case?->fits($type, $childTypes) ? $case : null;
    }

    /**
     * The fitting block of a sub-field row, shaped as the admin form posts it and an export holds
     * it: `type` and `block` as plain values and the `children` rows beneath.
     *
     * @param  array<string, mixed>  $row
     */
    public static function forRow(array $row): ?self
    {
        $type = SubFieldType::tryFrom((int) ($row['type'] ?? 0));

        if ($type === null) {
            return null;
        }

        $childTypes = [];

        foreach ($type->hasChildren() ? (array) ($row['children'] ?? []) : [] as $child) {
            $childType = SubFieldType::tryFrom((int) (((array) $child)['type'] ?? 0));

            if ($childType !== null) {
                $childTypes[] = $childType;
            }
        }

        return self::resolve($row['block'] ?? null, $type, $childTypes);
    }

    /**
     * The blocks with what they fit, for the admin form to offer only the ones a sub-field can take.
     *
     * @return Collection<int, array{name: string, value: string, title: string, type: int, requiresChildren: bool|null, requiresBooleanChildren: bool}>
     */
    public static function forEditor(): Collection
    {
        return collect(self::cases())
            ->map(fn (self $block) => [
                'name' => $block->name,
                'value' => $block->value,
                'title' => $block->title(),
                'type' => $block->type()->value,
                'requiresChildren' => $block->requiresChildren(),
                'requiresBooleanChildren' => $block->requiresBooleanChildren(),
            ]);
    }
}
