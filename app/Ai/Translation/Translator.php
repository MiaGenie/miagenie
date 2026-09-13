<?php

namespace App\Ai\Translation;

use App\Ai\Agents\TranslationAgent;
use App\Concerns\ConfiguresAiProvider;
use Inovector\Mixpost\Util;
use RuntimeException;

/**
 * Translates texts into a set of locales with the default AI provider.
 *
 * Stateless: it neither knows nor cares which model the text belongs to, so any translatable
 * attribute — or anything else — can go through it.
 */
class Translator
{
    use ConfiguresAiProvider;

    public function __construct(protected TranslationAgent $agent = new TranslationAgent) {}

    /**
     * Translate one text from $from into each locale in $to.
     *
     * @param  list<string>  $to  locale codes in their long form, e.g. `pt-PT`
     * @return array<string, string> translations keyed by the requested locale
     *
     * @throws RuntimeException when the model leaves a requested locale out
     */
    public function translate(string $text, string $from, array $to): array
    {
        return array_map(
            fn (array $texts): string => $texts[0],
            $this->translateMany([$text], $from, $to)
        );
    }

    /**
     * Translate a list of texts, keeping their order, from $from into each locale in $to.
     *
     * One prompt covers every text and locale; the list shape is what a sub-field's allowed
     * values need, since those are matched to the source by position.
     *
     * @param  list<string>  $texts
     * @param  list<string>  $to  locale codes in their long form, e.g. `pt-PT`
     * @return array<string, list<string>> per requested locale, the translations in the order of $texts
     *
     * @throws RuntimeException when the model leaves a requested locale out or returns a different count
     */
    public function translateMany(array $texts, string $from, array $to): array
    {
        $texts = array_values($texts);
        $to = array_values(array_unique(array_filter($to, fn (string $locale) => $locale !== $from)));

        if ($to === [] || array_filter($texts, fn (string $text) => trim($text) !== '') === []) {
            return [];
        }

        $this->configureAiProvider(config('ai.default'));

        $response = $this->agent->prompt($this->prompt($texts, $from, $to));

        $translations = [];

        foreach ((array) ($response['translations'] ?? []) as $translation) {
            $locale = $translation['locale'] ?? null;

            if (in_array($locale, $to, true)) {
                $translations[$locale] = array_values(array_map('strval', (array) ($translation['texts'] ?? [])));
            }
        }

        $missing = array_diff($to, array_keys($translations));

        if ($missing !== []) {
            throw new RuntimeException('No translation was returned for: '.implode(', ', $missing));
        }

        foreach ($translations as $locale => $translated) {
            if (count($translated) !== count($texts)) {
                throw new RuntimeException("The translation for {$locale} returned ".count($translated).' texts instead of '.count($texts).'.');
            }
        }

        return $translations;
    }

    /**
     * @param  list<string>  $texts
     * @param  list<string>  $to
     */
    public function prompt(array $texts, string $from, array $to): string
    {
        $targets = implode("\n", array_map(
            fn (string $locale) => "- {$locale} ({$this->localeName($locale)})",
            $to
        ));

        $sources = implode("\n\n", array_map(
            fn (int $index, string $text) => ($index + 1).".\n```\n{$text}\n```",
            array_keys($texts),
            $texts
        ));

        return "Source locale: {$from} ({$this->localeName($from)})\n"
            ."Target locales:\n{$targets}\n\n"
            .'Texts to translate ('.count($texts)."):\n{$sources}";
    }

    /**
     * The English name of a configured locale, or the code itself when it is not configured.
     */
    protected function localeName(string $locale): string
    {
        foreach ((array) Util::config('locales') as $configured) {
            if (($configured['long'] ?? null) === $locale) {
                return $configured['english'];
            }
        }

        return $locale;
    }
}
