<?php

namespace App\Genie\Schema;

use App\Enums\SubFieldType;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Support\Collection;

/**
 * Compiles what the page needs to know about a strategy field's sub-fields, but the compiled JSON
 * Schema cannot carry.
 *
 * `StepSchemaBuilder::decorate()` deliberately emits none of `editable`, `icon`, `class` or
 * `position`: they say nothing about the contract sent to the provider, and Illuminate's serializer
 * drops unknown keywords anyway. Editability still has to reach the strategy pages, so it travels
 * beside the schema in a tree of the same shape — node for node — and the page walks the two
 * together.
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

        $node = ['editable' => $this->isEditable($subField, $children)];

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
