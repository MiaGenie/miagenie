<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Translates one piece of admin content into any number of locales in a single prompt.
 *
 * The content is UI copy and prompt text (field labels, help texts, step messages), so the
 * cheapest model of the provider is plenty. Step messages embed `{{{placeholder}}}` tokens
 * and some texts are HTML, which is why the instructions insist on leaving markup alone.
 */
#[UseCheapestModel]
#[Timeout(120)]
class TranslationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
        You are a professional translator for a marketing content application.
        Translate every numbered source text into every requested target locale, keeping the meaning, tone, register and approximate length of each source.
        For each locale return the translations as a list in exactly the same order and count as the numbered source texts, without the numbers.
        Return only the translations — no explanations, no quotes around the text.
        Preserve verbatim, without translating or altering: HTML tags and attributes, Markdown syntax, line breaks, URLs, and any placeholder token written between triple braces such as {{{briefings.company_name}}} or {{{strategy.FULL}}}.
        Use the locale codes exactly as they were given.
        TEXT;
    }

    /**
     * One entry per requested locale, holding the translations of the source texts in order —
     * a single text is a one-item list. Every property is required so the schema compiles in
     * strict mode.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'translations' => $schema->array()
                ->items(
                    $schema->object([
                        'locale' => $schema->string()
                            ->description('The target locale code, exactly as requested.')
                            ->required(),
                        'texts' => $schema->array()
                            ->items($schema->string())
                            ->description('The translations of the source texts into that locale, in the same order.')
                            ->required(),
                    ])
                )
                ->required(),
        ];
    }
}
