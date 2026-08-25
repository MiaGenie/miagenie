<?php

namespace App\Genie\Schema;

use App\Enums\SubFieldType;
use App\Enums\VersionGroupType;
use App\Models\RuleStep;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Collection;
use Laravel\Ai\ObjectSchema;
use RuntimeException;

/**
 * Compiles a step's structured-output JSON Schema from the sub-field trees of the
 * strategy fields it writes to.
 *
 * The root object carries one property per entry in the step's `output` array, keyed by the
 * strategy field's `code_name`, which is the shape `GenieOutputStrategy` reads back.
 *
 * A field's own type comes from its root sub-fields: a single root sub-field *is* the field's
 * node, while several roots are wrapped in an implicit object.
 */
class StepSchemaBuilder
{
    public function __construct(protected JsonSchema $factory = new JsonSchemaTypeFactory) {}

    /**
     * Build the compiled schema for a step, or null when it cannot be generated.
     *
     * @return array<string, mixed>|null
     */
    public function forStep(RuleStep $step, ?string $locale = null): ?array
    {
        return new ObjectSchema(
            $this->propertiesForStep($step, $locale),
            $this->schemaName($step),
            strict: true,
        )->toSchema();
    }

    /**
     * The compiled schema, or null when the step cannot produce one.
     *
     * For describing a step — the run log, an admin preview — where a step that cannot state its
     * contract should be reported as having no schema rather than take the caller down with it.
     * Generation must keep using forStep(), which refuses.
     *
     * @return array<string, mixed>|null
     */
    public function tryForStep(RuleStep $step, ?string $locale = null): ?array
    {
        try {
            return $this->forStep($step, $locale);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Build the compiled schema for an ordered set of strategy fields.
     *
     * @param  iterable<int, VersionField>  $fields
     * @return array<string, mixed>|null
     */
    public function forFields(iterable $fields, ?string $locale = null, string $name = 'schema_definition'): ?array
    {
        $properties = $this->propertiesForFields($fields, $locale);

        if ($properties === null) {
            return null;
        }

        return new ObjectSchema($properties, $name, strict: true)->toSchema();
    }

    /**
     * Build the schema closure consumed by the AI SDK's structured output, or null when the
     * step produces free text and should be prompted without a schema.
     */
    public function closureForStep(RuleStep $step, ?string $locale = null): ?Closure
    {
        if ($step->response_format !== 'json_schema') {
            return null;
        }

        return function (JsonSchema $schema) use ($step, $locale): array {
            return new self($schema)->propertiesForStep($step, $locale);
        };
    }

    /**
     * Resolve a step's root schema properties, compiled from the sub-field trees of the fields it
     * writes to.
     *
     * There is no second source. The step's stored `json_schema` is legacy: reading it hid genuine
     * misconfiguration — a step naming a field that does not exist, or a field with no sub-field
     * tree — behind a schema that still looked plausible, and froze the contract at whatever the
     * column happened to hold, so editing a sub-field no longer changed what was sent. A step that
     * cannot state its contract refuses instead.
     *
     * @return array<string, Type>
     */
    public function propertiesForStep(RuleStep $step, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $fields = $this->resolveFields($step);

        $properties = $fields === null ? null : $this->propertiesForFields($fields, $locale);

        if ($properties === null) {
            throw new RuntimeException(sprintf(
                'Step [%d] "%s" cannot compile a schema: %s',
                $step->id,
                $step->name,
                $this->describeUnresolvable($step),
            ));
        }

        return $properties;
    }

    /**
     * Say precisely which of the step's outputs is the problem.
     */
    protected function describeUnresolvable(RuleStep $step): string
    {
        $codeNames = collect($step->output ?? [])->filter()->values();

        if ($codeNames->isEmpty()) {
            return 'it declares no output field.';
        }

        $fields = VersionField::query()
            ->where('version_id', $step->rule->version_id)
            ->where('group_type', VersionGroupType::STRATEGIES)
            ->whereIn('code_name', $codeNames->all())
            ->with('subFields')
            ->get()
            ->keyBy('code_name');

        $missing = $codeNames->reject(fn (string $codeName) => $fields->has($codeName));

        $withoutSubFields = $codeNames
            ->filter(fn (string $codeName) => $fields->has($codeName))
            ->filter(fn (string $codeName) => $fields->get($codeName)->subFields->isEmpty());

        return collect([
            $missing->isNotEmpty() ? 'no such field in version '.$step->rule->version_id.': '.$missing->implode(', ') : null,
            $withoutSubFields->isNotEmpty() ? 'no sub-fields defined for: '.$withoutSubFields->implode(', ') : null,
        ])->filter()->implode('; ').'.';
    }

    /**
     * Build the root properties, keyed by strategy field code_name.
     *
     * @param  iterable<int, VersionField>  $fields
     * @return array<string, Type>|null
     */
    public function propertiesForFields(iterable $fields, ?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();

        $properties = [];

        foreach ($fields as $field) {
            $type = $this->typeForField($field, $locale);

            if ($type === null) {
                return null;
            }

            $properties[$field->code_name] = $type;
        }

        return $properties === [] ? null : $properties;
    }

    /**
     * Resolve a step's output fields in output order, with their sub-field tree eager loaded.
     *
     * Returns null when the step names a field that does not exist, so the caller falls back
     * to the hand-written schema rather than silently generating a partial one.
     *
     * @return Collection<int, VersionField>|null
     */
    public function resolveFields(RuleStep $step): ?Collection
    {
        $codeNames = collect($step->output ?? [])
            ->map(fn ($codeName) => trim((string) $codeName))
            ->filter()
            ->unique()
            ->values();

        if ($codeNames->isEmpty()) {
            return null;
        }

        $fields = VersionField::query()
            ->where('version_id', $step->rule->version_id)
            ->where('group_type', VersionGroupType::STRATEGIES)
            ->whereIn('code_name', $codeNames->all())
            ->with('subFields')
            ->get()
            ->keyBy('code_name');

        $ordered = $codeNames->map(fn (string $codeName) => $fields->get($codeName));

        return $ordered->contains(null) ? null : $ordered;
    }

    /**
     * Build the type describing a single strategy field's value.
     */
    protected function typeForField(VersionField $field, string $locale): ?Type
    {
        $subFields = $field->subFields instanceof Collection
            ? $field->subFields
            : $field->subFields()->get();

        $roots = $subFields->whereNull('parent_id')->sortBy('position')->values();

        if ($roots->isEmpty()) {
            return null;
        }

        if ($roots->count() === 1) {
            return $this->buildType($roots->first(), $subFields, $locale);
        }

        return $this->factory
            ->object($this->buildProperties($roots, $subFields, $locale))
            ->required();
    }

    /**
     * Build the type for a single sub-field, recursing into its children.
     *
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function buildType(VersionFieldSubField $subField, Collection $all, string $locale): Type
    {
        $children = $all
            ->where('parent_id', $subField->id)
            ->sortBy('position')
            ->values();

        $type = match ($subField->type) {
            SubFieldType::OBJECT => $this->factory->object(
                $this->buildProperties($children, $all, $locale)
            ),
            SubFieldType::ARRAY => $this->buildArray($subField, $children, $all, $locale),
            SubFieldType::STRING => $this->buildString($subField),
            SubFieldType::BOOLEAN => $this->factory->boolean(),
        };

        return $this->decorate($type, $subField, $locale);
    }

    /**
     * Build an array type, whose children describe the shape of each item.
     *
     * Without children the items are strings, and the sub-field's own length, pattern and
     * enum constrain each of them — an array node has nowhere else to put them.
     *
     * @param  Collection<int, VersionFieldSubField>  $children
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function buildArray(
        VersionFieldSubField $subField,
        Collection $children,
        Collection $all,
        string $locale
    ): Type {
        $type = $this->factory->array();

        $type = $children->isEmpty()
            ? $type->items($this->buildString($subField))
            : $type->items($this->factory->object($this->buildProperties($children, $all, $locale)));

        if ($subField->min_items !== null) {
            $type = $type->min($subField->min_items);
        }

        if ($subField->max_items !== null) {
            $type = $type->max($subField->max_items);
        }

        return $type;
    }

    /**
     * Build a string type with its length and enum constraints.
     */
    protected function buildString(VersionFieldSubField $subField): Type
    {
        $type = $this->factory->string();

        if ($subField->min_length !== null) {
            $type = $type->min($subField->min_length);
        }

        if ($subField->max_length !== null) {
            $type = $type->max($subField->max_length);
        }

        if (filled($subField->pattern)) {
            $type = $type->pattern($subField->pattern);
        }

        $enum = array_values(array_filter((array) ($subField->enum_values ?? [])));

        if ($enum !== []) {
            $type = $type->enum($enum);
        }

        return $type;
    }

    /**
     * Map a set of sub-fields to schema properties keyed by code_name.
     *
     * @param  Collection<int, VersionFieldSubField>  $subFields
     * @param  Collection<int, VersionFieldSubField>  $all
     * @return array<string, Type>
     */
    protected function buildProperties(Collection $subFields, Collection $all, string $locale): array
    {
        $properties = [];

        foreach ($subFields as $subField) {
            $properties[$subField->sub_code_name] = $this->buildType($subField, $all, $locale);
        }

        return $properties;
    }

    /**
     * Apply the annotations and presence shared by every type.
     *
     * `icon`, `class`, `block`, `editable` and `position` are deliberately not emitted: they
     * drive the frontend, and Illuminate's serializer drops unknown keywords anyway.
     */
    protected function decorate(Type $type, VersionFieldSubField $subField, string $locale): Type
    {
        $title = $subField->getTranslation('name', $locale);

        if (filled($title)) {
            $type = $type->title($title);
        }

        $description = $subField->getTranslation('description', $locale);

        if (filled($description)) {
            $type = $type->description($description);
        }

        // Strict mode requires every property to be listed as required, so optional sub-fields
        // stay present and become nullable instead of being omitted.
        return $subField->required ? $type->required() : $type->required()->nullable();
    }

    /**
     * Build the schema name sent to the provider.
     */
    protected function schemaName(RuleStep $step): string
    {
        $name = preg_replace("/[^\sa-zA-Z0-9_-]/", '', (string) $step->name);

        return str($name)->snake()->toString() ?: 'schema_definition';
    }
}
