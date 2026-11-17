<?php

namespace App\Genie\Schema;

use App\Enums\StrategyBlock;
use App\Enums\StrategyStyle;
use App\Enums\SubFieldType;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Support\Collection;

/**
 * Compiles what the page needs to know about a strategy field's sub-fields, but the compiled JSON
 * Schema cannot carry.
 *
 * `StepSchemaBuilder::decorate()` deliberately emits none of `editable`, `icon`, `class`, `block`
 * or `position`: they say nothing about the contract sent to the provider, and Illuminate's
 * serializer drops unknown keywords anyway. Editability and styling still have to reach the
 * strategy pages, so they travel beside the schema in a tree of the same shape — node for node —
 * and the page walks the two together.
 *
 * A node carries `block` (a `StrategyBlock` that fits it) and `class` (its `StrategyStyle` tokens)
 * only when the sub-field sets them; without a block the page infers the layout from the schema.
 */
class SubFieldMetaBuilder
{
    /**
     * The meta for a set of strategy fields, keyed by `code_name`.
     *
     * @param  iterable<int, VersionField>  $fields
     * @return array<string, array<string, mixed>>
     */
    public function forFields(iterable $fields): array
    {
        $meta = [];

        foreach ($fields as $field) {
            $node = $this->forField($field);

            if ($node !== null) {
                $meta[$field->code_name] = $node;
            }
        }

        return $meta;
    }

    /**
     * A field's own node.
     *
     * The single-root collapse of `StepSchemaBuilder::typeForField()` is repeated here: one root
     * sub-field *is* the field's node, while several are wrapped in an implicit object. Were the
     * two to disagree the page would read the wrong node's flag.
     *
     * @return array<string, mixed>|null
     */
    public function forField(VersionField $field): ?array
    {
        $subFields = $field->subFields instanceof Collection
            ? $field->subFields
            : $field->subFields()->get();

        $roots = $subFields->whereNull('parent_id')->sortBy('position')->values();

        if ($roots->isEmpty()) {
            return null;
        }

        if ($roots->count() === 1) {
            return $this->forSubField($roots->first(), $subFields);
        }

        return [
            'editable' => false,
            'properties' => $this->properties($roots, $subFields),
        ];
    }

    /**
     * One sub-field's node, recursing into its children.
     *
     * @param  Collection<int, VersionFieldSubField>  $all
     * @return array<string, mixed>
     */
    protected function forSubField(VersionFieldSubField $subField, Collection $all): array
    {
        $children = $all
            ->where('parent_id', $subField->id)
            ->sortBy('position')
            ->values();

        $node = ['editable' => $this->isEditable($subField, $children)] + $this->styling($subField, $children);

        return match ($subField->type) {
            SubFieldType::OBJECT => $node + ['properties' => $this->properties($children, $all)],
            SubFieldType::ARRAY => $children->isEmpty()
                ? $node
                : $node + ['items' => [
                    'editable' => false,
                    'properties' => $this->properties($children, $all),
                ]],
            SubFieldType::STRING, SubFieldType::BOOLEAN => $node,
        };
    }

    /**
     * The sub-field's block and style tokens, as far as they are valid for it.
     *
     * Checked against the shape rather than trusted, as editability is: the stored block may
     * predate a change of type or children, and a row written by any other route than the admin
     * form may hold anything.
     *
     * @param  Collection<int, VersionFieldSubField>  $children
     * @return array{block?: string, class?: string}
     */
    protected function styling(VersionFieldSubField $subField, Collection $children): array
    {
        $block = StrategyBlock::resolve(
            $subField->block,
            $subField->type,
            $children->map(fn (VersionFieldSubField $child) => $child->type)->all(),
        );

        $class = StrategyStyle::sanitize($subField->class);

        return array_filter([
            'block' => $block?->value,
            'class' => $class,
        ], fn (?string $value) => $value !== null);
    }

    /**
     * Whether the customer may edit this sub-field.
     *
     * The stored flag is checked against the shape rather than trusted on its own, the same way
     * `HandlesSubFields::resolveEditable()` settles it on save: a tree changed by any other route
     * cannot hand the page a `true` its own children have since made impossible.
     *
     * @param  Collection<int, VersionFieldSubField>  $children
     */
    protected function isEditable(VersionFieldSubField $subField, Collection $children): bool
    {
        $allowed = VersionFieldSubField::allowsEditing(
            $subField->type,
            $children->isNotEmpty(),
            filled($subField->enum_values),
        );

        return $allowed && (bool) $subField->editable;
    }

    /**
     * @param  Collection<int, VersionFieldSubField>  $subFields
     * @param  Collection<int, VersionFieldSubField>  $all
     * @return array<string, array<string, mixed>>
     */
    protected function properties(Collection $subFields, Collection $all): array
    {
        $properties = [];

        foreach ($subFields as $subField) {
            $properties[$subField->sub_code_name] = $this->forSubField($subField, $all);
        }

        return $properties;
    }
}
