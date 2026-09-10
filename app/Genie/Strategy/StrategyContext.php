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
 * so far rather than a handful of named fields.
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

        $bullets = [];

        foreach ($value as $item) {
            $rendered = is_array($item)
                ? $this->renderProperties($children, $all, $item)
                : $this->scalar($item);

            if (blank($rendered)) {
                continue;
            }

            $bullets[] = $this->bullet($rendered);
        }

        return implode("\n", $bullets);
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

            $bullets = [];

            foreach ($value as $item) {
                $rendered = $this->renderUnknown($item);

                if (blank($rendered)) {
                    continue;
                }

                $bullets[] = $this->bullet($rendered);
            }

            return implode("\n", $bullets);
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
