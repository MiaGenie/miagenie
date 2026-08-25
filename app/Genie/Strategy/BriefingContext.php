<?php

namespace App\Genie\Strategy;

use App\Models\AiRun;
use App\Models\VersionField;
use Illuminate\Support\Collection;

/**
 * The customer's briefing, rendered for a prompt.
 *
 * Everything a step's message can ask of the briefing is answered here, in both the shapes it is
 * asked for: `answers()` for the field-by-field `{{{briefings.<code_name>}}}` substitutions, and
 * `full()` for `{{{briefings.FULL}}}`, the whole thing as one block for a step that needs the
 * customer's context rather than a handful of named fields. Sharing one class is what keeps the two
 * agreeing about what an answer says — an option code reads as the label a human wrote either way —
 * and lets the version's fields be read once instead of once per answer.
 *
 * The images and files a briefing carries are not described yet; that comes later.
 */
class BriefingContext
{
    /**
     * The version's briefing fields, keyed by code_name, in the order the customer was asked.
     *
     * @var Collection<string, VersionField>|null
     */
    protected ?Collection $fields = null;

    public function __construct(
        protected AiRun $run,
        protected string $locale,
    ) {}

    /**
     * Every stored answer, keyed by its field's code_name.
     *
     * Walks the stored content rather than the version's fields, so a key the version no longer
     * defines still produces a replacement: a message written against an older briefing keeps
     * resolving.
     *
     * @return array<string, mixed>
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
     * The whole briefing as one labelled block.
     *
     * Walks the version's fields rather than the stored content, so the reading order is the order
     * the questions were asked rather than whatever order the JSON happens to hold. A question the
     * customer skipped is left out entirely: an empty heading tells the model nothing, and a gap it
     * cannot see cannot be mistaken for an answer.
     *
     * Files and images are not described yet — that comes later.
     */
    public function full(): string
    {
        $content = $this->content();

        $blocks = [];

        foreach ($this->fields() as $codeName => $field) {
            if ($this->isFile($field)) {
                continue;
            }

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
        return $this->run->briefing?->content ?? [];
    }

    /**
     * One answer as the model should read it.
     */
    protected function render(string $codeName, mixed $value): mixed
    {
        if (! is_array($value) && blank($value)) {
            return $value;
        }

        $field = $this->fields()->get($codeName);

        if (is_array($value)) {
            // A list of choices cannot be read without the field that defines them, so a stored key
            // the version has since dropped renders as nothing rather than as a row of bare codes.
            if (! $field) {
                return '';
            }

            if ($this->isFile($field)) {
                return $value['path'] ?? '';
            }

            $labels = array_map(
                fn ($item) => is_string($item) ? $this->optionLabel($field, $item) : null,
                array_values($value),
            );

            return implode(', ', array_filter($labels, fn ($label) => filled($label)));
        }

        // A choice saved on its own is the same answer as a choice saved in a list of one. Free
        // text has no option to match and resolves to itself.
        return is_string($value) ? $this->optionLabel($field, $value) : $value;
    }

    /**
     * The label an option code stands for.
     *
     * A stored briefing can name an option that was later renamed or removed, so an unknown code
     * falls back to the code itself rather than disappearing from the prompt.
     */
    protected function optionLabel(?VersionField $field, string $value): string
    {
        if (! $field) {
            return $value;
        }

        $options = $field->relationLoaded('options') ? $field->options : $field->options()->get();

        $option = $options->firstWhere('code_name', $value);

        return $option ? ($option->getTranslation('name', $this->locale) ?: $value) : $value;
    }

    protected function label(VersionField $field, string $codeName): string
    {
        $name = $field->getTranslation('name', $this->locale);

        return filled($name) ? $name : $codeName;
    }

    protected function isFile(VersionField $field): bool
    {
        return $field->field_type?->name === 'FILE';
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

        $fields = $version->relationLoaded('briefings')
            ? $version->briefings
            : $version->briefings()->with('options')->get();

        return $this->fields = $fields->keyBy('code_name');
    }
}
