<?php

namespace App\Genie\Strategy;

use App\Enums\SubFieldType;
use App\Models\AiRun;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Support\Collection;

/**
 * The strategy written so far, rendered for a prompt.
 *
 * The strategy side of what BriefingContext does for the briefing, and deliberately the same shape:
 * `answers()` for the field-by-field `{{{strategy.<code_name>}}}` substitutions, and `full()` for
 * `{{{strategy.FULL}}}`, the whole strategy as one block for a step that needs everything decided
 * so far rather than a handful of named fields. A strategy field is rarely a single value, so
 * `subAnswers()` adds one level of addressing on top: `{{{strategy.<code_name>.<sub_field>}}}` for a
 * step that wants one property of a field — or, when the field is a list, that property of every
 * item.
 *
 * A strategy value's shape is described by its field's sub-field tree, so a value is rendered by
 * walking the two together: the tree supplies the labels and the reading order the admin defined,
 * the value supplies the prose. Without it a nested field can only be JSON, which is what the model
 * used to be handed.
 */
class StrategyContext
{
    /**
     * The version's strategy fields, keyed by code_name, in the order the admin defined them.
     *
     * @var Collection<string, VersionField>|null
     */
    protected ?Collection $fields = null;

    public function __construct(
        protected AiRun $run,
        protected string $locale,
    ) {}

    /**
     * Every stored field, keyed by its code_name.
     *
     * Walks the stored content rather than the version's fields, so a key the version no longer
     * defines still produces a replacement: a message written against an older version keeps
     * resolving.
     *
     * @return array<string, string>
     */
    public function answers(): array
    {
        $answers = [];

        foreach ($this->content() as $codeName => $value) {
            $answers[$codeName] = $this->render($codeName, $value);
        }

        return $answers;
    }

    /**
     * Every stored field's properties, one level down, keyed `<code_name>.<sub_code_name>`.
     *
     * A string, boolean or object property is its value; an array property is every value as a
     * list, one per line. When the field itself is a list of objects the property is plucked across
     * every item, so `content_pillars.title` is every pillar's title. Deeper paths are not
     * addressed: a placeholder for one passes through the prompt untouched, like any unknown key.
     *
     * Walks the stored content, as answers() does, so a retired field still resolves from its own
     * keys.
     *
     * @return array<string, string>
     */
    public function subAnswers(): array
    {
        $answers = [];

        foreach ($this->content() as $codeName => $value) {
            foreach ($this->subValues($codeName, $value) as $subCodeName => $rendered) {
                $answers[$codeName.'.'.$subCodeName] = $rendered;
            }
        }

        return $answers;
    }

    /**
     * The whole strategy as one labelled block.
     *
     * Walks the version's fields rather than the stored content, so the reading order is the order
     * the admin arranged the fields in rather than whatever order the JSON happens to hold. A field
     * no step has written yet is left out entirely: an empty heading tells the model nothing, and a
     * gap it cannot see cannot be mistaken for a decision already taken.
     */
    public function full(): string
    {
        $content = $this->content();

        $blocks = [];

        foreach ($this->fields() as $codeName => $field) {
            $answer = $this->render($codeName, $content[$codeName] ?? null);

            if (blank($answer)) {
                continue;
            }

            $blocks[] = '**'.$this->label($field, $codeName).'**'."\n".$answer;
        }

        return implode("\n\n", $blocks);
    }

    /**
     * @return array<string, mixed>
     */
    protected function content(): array
    {
        return $this->run->strategy?->content ?? [];
    }

    /**
     * One field's value — or a slice of one, such as a single content pillar — rendered with the
     * labels its sub-field tree defines, for a run type that needs part of the strategy on its own.
     */
    public function renderField(string $codeName, mixed $value): string
    {
        return $this->render($codeName, $value);
    }

    /**
     * One field's value as the model should read it.
     */
    protected function render(string $codeName, mixed $value): string
    {
        if (! is_array($value) && blank($value)) {
            return '';
        }

        $field = $this->fields()->get($codeName);

        $subFields = $field ? $this->subFieldsOf($field) : new Collection;

        $roots = $subFields->whereNull('parent_id')->sortBy('position')->values();

        // The single-root collapse of StepSchemaBuilder::typeForField() and
        // SubFieldMetaBuilder::forField() is repeated here: one root sub-field *is* the field's
        // node, while several roots are an implicit object whose properties are the roots. Were the
        // three to disagree, this would label the wrong node.
        if ($roots->count() === 1) {
            return $this->renderSubField($roots->first(), $subFields, $value);
        }

        if ($roots->isNotEmpty()) {
            return $this->renderProperties($roots, $subFields, $value);
        }

        // A field the version has since dropped still has its own keys to read: the value is
        // rendered structurally from itself rather than disappearing from the prompt.
        return $this->renderUnknown($value);
    }

    /**
     * One field's properties, rendered, keyed by sub_code_name.
     *
     * Which keys a field has follows the same single-root collapse as render(): several roots are
     * the properties themselves, one OBJECT root lends its children, and one ARRAY root with
     * children lends them too — read across every item, since the value is a list. A field that is
     * a bare string or a list of strings has no property to address. A field the version no longer
     * describes is read from the value's own keys.
     *
     * @return array<string, string>
     */
    protected function subValues(string $codeName, mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        $field = $this->fields()->get($codeName);

        $all = $field ? $this->subFieldsOf($field) : new Collection;

        $roots = $all->whereNull('parent_id')->sortBy('position')->values();

        $plucked = array_is_list($value);

        if ($roots->count() === 1) {
            $root = $roots->first();

            $properties = match ($root->type) {
                SubFieldType::OBJECT, SubFieldType::ARRAY => $this->childrenOf($root, $all),
                SubFieldType::STRING, SubFieldType::BOOLEAN => new Collection,
            };

            if ($properties->isEmpty()) {
                return [];
            }
        } else {
            $properties = $roots;
        }

        $collected = $plucked ? $this->pluck($value) : array_map(fn ($item) => [$item], $value);

        $described = [];
        $rendered = [];

        foreach ($properties as $subField) {
            $described[] = $subField->sub_code_name;

            if (! array_key_exists($subField->sub_code_name, $collected)) {
                continue;
            }

            $rendered[$subField->sub_code_name] = $plucked
                ? $this->renderPlucked($subField, $all, $collected[$subField->sub_code_name])
                : $this->renderSubValue($subField, $all, $collected[$subField->sub_code_name][0]);
        }

        // A key the tree no longer describes still answers under its own name, as it does in
        // renderProperties().
        foreach ($collected as $key => $values) {
            if (in_array($key, $described, true)) {
                continue;
            }

            $rendered[(string) $key] = $plucked
                ? $this->renderPlucked(null, $all, $values)
                : $this->renderSubValue(null, $all, $values[0]);
        }

        return $rendered;
    }

    /**
     * Every key across a list of items, each with the values the items hold for it, in the order
     * the keys are first met.
     *
     * @param  array<int, mixed>  $items
     * @return array<string, array<int, mixed>>
     */
    protected function pluck(array $items): array
    {
        $collected = [];

        foreach ($items as $item) {
            if (! is_array($item) || array_is_list($item)) {
                continue;
            }

            foreach ($item as $key => $value) {
                $collected[$key][] = $value;
            }
        }

        return $collected;
    }

    /**
     * One property's value as a substitution: a list as bullets, anything else as itself.
     *
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function renderSubValue(?VersionFieldSubField $subField, Collection $all, mixed $value): string
    {
        $isList = $subField
            ? $subField->type === SubFieldType::ARRAY
            : is_array($value) && array_is_list($value);

        if ($isList && is_array($value)) {
            return $this->bullets(array_map(
                fn ($item) => $this->renderItem($subField, $all, $item),
                array_values($value),
            ));
        }

        return $this->renderItem($subField, $all, $value);
    }

    /**
     * One property plucked across a list field, as bullets.
     *
     * An array property contributes every item's values rather than one list per item, so the
     * topics of every pillar read as one list of topics.
     *
     * @param  Collection<int, VersionFieldSubField>  $all
     * @param  array<int, mixed>  $values
     */
    protected function renderPlucked(?VersionFieldSubField $subField, Collection $all, array $values): string
    {
        $items = [];

        foreach ($values as $value) {
            if (is_array($value) && array_is_list($value)) {
                array_push($items, ...array_values($value));
            } else {
                $items[] = $value;
            }
        }

        return $this->bullets(array_map(fn ($item) => $this->renderItem($subField, $all, $item), $items));
    }

    /**
     * One element of a property: an object as its labelled lines, a scalar as itself.
     *
     * An ARRAY sub-field arrives here per item, so its children describe the item when it has
     * any, and otherwise the item is one of its strings.
     *
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function renderItem(?VersionFieldSubField $subField, Collection $all, mixed $item): string
    {
        if (! $subField) {
            return $this->renderUnknown($item);
        }

        $children = $this->childrenOf($subField, $all);

        return match ($subField->type) {
            SubFieldType::OBJECT => $this->renderProperties($children, $all, $item),
            SubFieldType::ARRAY => $children->isEmpty()
                ? $this->scalar($item)
                : $this->renderProperties($children, $all, $item),
            SubFieldType::STRING, SubFieldType::BOOLEAN => $this->scalar($item),
        };
    }

    /**
     * One sub-field's value, recursing into its children.
     *
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function renderSubField(VersionFieldSubField $subField, Collection $all, mixed $value): string
    {
        $children = $this->childrenOf($subField, $all);

        return match ($subField->type) {
            SubFieldType::OBJECT => $this->renderProperties($children, $all, $value),
            SubFieldType::ARRAY => $children->isEmpty()
                ? $this->renderList($value)
                : $this->renderItems($children, $all, $value),
            SubFieldType::STRING, SubFieldType::BOOLEAN => $this->scalar($value),
        };
    }

    /**
     * An object's children as one labelled line each.
     *
     * @param  Collection<int, VersionFieldSubField>  $subFields
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function renderProperties(Collection $subFields, Collection $all, mixed $value): string
    {
        if (! is_array($value)) {
            return $this->scalar($value);
        }

        $described = [];
        $lines = [];

        foreach ($subFields as $subField) {
            $described[] = $subField->sub_code_name;

            if (! array_key_exists($subField->sub_code_name, $value)) {
                continue;
            }

            $lines[] = $this->line(
                $this->subLabel($subField),
                $this->renderSubField($subField, $all, $value[$subField->sub_code_name]),
                $this->isStructural($subField, $all),
            );
        }

        // A key the tree no longer describes keeps its own key as its label, the same way
        // BriefingContext falls back to an option's code rather than dropping the answer.
        foreach ($value as $key => $item) {
            if (in_array($key, $described, true)) {
                continue;
            }

            $lines[] = $this->line((string) $key, $this->renderUnknown($item), $this->isStructure($item));
        }

        return $this->join($lines);
    }

    /**
     * An array of objects as one bullet per item.
     *
     * @param  Collection<int, VersionFieldSubField>  $children
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function renderItems(Collection $children, Collection $all, mixed $value): string
    {
        if (! is_array($value)) {
            return $this->scalar($value);
        }

        return $this->bullets(array_map(
            fn ($item) => is_array($item) ? $this->renderProperties($children, $all, $item) : $this->scalar($item),
            array_values($value),
        ));
    }

    /**
     * An array of scalars, joined the way BriefingContext joins a list of choices.
     */
    protected function renderList(mixed $value): string
    {
        if (! is_array($value)) {
            return $this->scalar($value);
        }

        $items = array_map($this->scalar(...), array_values($value));

        return implode(', ', array_filter($items, fn (string $item) => filled($item)));
    }

    /**
     * A value with no sub-field to describe it, rendered structurally from its own keys.
     */
    protected function renderUnknown(mixed $value): string
    {
        if (! is_array($value)) {
            return $this->scalar($value);
        }

        if (array_is_list($value)) {
            if (! $this->isStructure($value)) {
                return $this->renderList($value);
            }

            return $this->bullets(array_map($this->renderUnknown(...), $value));
        }

        $lines = [];

        foreach ($value as $key => $item) {
            $lines[] = $this->line((string) $key, $this->renderUnknown($item), $this->isStructure($item));
        }

        return $this->join($lines);
    }

    /**
     * One labelled line, or a label above its own indented block when the value is a structure.
     */
    protected function line(string $label, string $rendered, bool $structural): string
    {
        if (blank($rendered)) {
            return '';
        }

        return $structural || str_contains($rendered, "\n")
            ? '**'.$label.'**'."\n".$this->indent($rendered)
            : '**'.$label.'**: '.$rendered;
    }

    /**
     * A single value as text, with a boolean read in the customer's language.
     */
    protected function scalar(mixed $value): string
    {
        if (is_array($value)) {
            return $this->renderUnknown($value);
        }

        if (is_bool($value)) {
            return (string) __($value ? 'general.yes' : 'general.no', [], $this->locale);
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Whether a value with no sub-field to describe it is a structure rather than something that
     * can be labelled beside its key.
     *
     * A list of scalars is not: it renders as one joined line, the same as the list an array
     * sub-field without children describes, and reads better beside its label than beneath it.
     */
    protected function isStructure(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        return ! array_is_list($value) || array_filter($value, is_array(...)) !== [];
    }

    /**
     * Whether a sub-field holds a structure rather than a value it can be labelled beside.
     *
     * @param  Collection<int, VersionFieldSubField>  $all
     */
    protected function isStructural(VersionFieldSubField $subField, Collection $all): bool
    {
        return match ($subField->type) {
            SubFieldType::OBJECT => true,
            SubFieldType::ARRAY => $this->childrenOf($subField, $all)->isNotEmpty(),
            SubFieldType::STRING, SubFieldType::BOOLEAN => false,
        };
    }

    protected function label(VersionField $field, string $codeName): string
    {
        $name = $field->getTranslation('name', $this->locale);

        return filled($name) ? $name : $codeName;
    }

    /**
     * A sub-field renamed to nothing still has to be named, so a blank label falls back to the
     * key the value is stored under.
     */
    protected function subLabel(VersionFieldSubField $subField): string
    {
        $name = $subField->getTranslation('name', $this->locale);

        return filled($name) ? $name : (string) $subField->sub_code_name;
    }

    /**
     * @param  Collection<int, VersionFieldSubField>  $all
     * @return Collection<int, VersionFieldSubField>
     */
    protected function childrenOf(VersionFieldSubField $subField, Collection $all): Collection
    {
        return $all->where('parent_id', $subField->id)->sortBy('position')->values();
    }

    /**
     * @return Collection<int, VersionFieldSubField>
     */
    protected function subFieldsOf(VersionField $field): Collection
    {
        return $field->relationLoaded('subFields') ? $field->subFields : $field->subFields()->get();
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function join(array $lines): string
    {
        return implode("\n", array_filter($lines, fn (string $line) => filled($line)));
    }

    protected function indent(string $block): string
    {
        $lines = array_map(
            fn (string $line) => $line === '' ? $line : '  '.$line,
            explode("\n", $block),
        );

        return implode("\n", $lines);
    }

    protected function bullet(string $block): string
    {
        return '- '.substr($this->indent($block), 2);
    }

    /**
     * Rendered items as one bullet each, blanks left out.
     *
     * @param  array<int, string>  $rendered
     */
    protected function bullets(array $rendered): string
    {
        $blocks = array_filter($rendered, fn (string $block) => filled($block));

        return implode("\n", array_map($this->bullet(...), $blocks));
    }

    /**
     * @return Collection<string, VersionField>
     */
    protected function fields(): Collection
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $version = $this->run->rule?->version;

        if (! $version) {
            return $this->fields = new Collection;
        }

        $fields = $version->relationLoaded('strategies')
            ? $version->strategies
            : $version->strategies()->with('subFields')->get();

        return $this->fields = $fields->keyBy('code_name');
    }
}
