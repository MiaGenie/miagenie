<?php

namespace Tests\Unit;

use App\Ai\Agents\TranslationAgent;
use App\Ai\Translation\Translator;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Prompts\AgentPrompt;
use RuntimeException;
use Tests\TestCase;

class TranslatorTest extends TestCase
{
    /**
     * Provider credentials live in the database; the translator under test skips that step.
     */
    protected function translator(): Translator
    {
        return new class extends Translator
        {
            protected function configureAiProvider(Lab|string $provider): void {}
        };
    }

    public function test_translations_are_keyed_by_the_requested_locale(): void
    {
        TranslationAgent::fake([
            ['translations' => [
                ['locale' => 'pt-PT', 'texts' => ['Olá']],
                ['locale' => 'es-ES', 'texts' => ['Hola']],
            ]],
        ]);

        $translations = $this->translator()->translate('Hello', 'en-GB', ['pt-PT', 'es-ES']);

        $this->assertSame(['pt-PT' => 'Olá', 'es-ES' => 'Hola'], $translations);
    }

    public function test_unrequested_and_source_locales_are_dropped(): void
    {
        TranslationAgent::fake([
            ['translations' => [
                ['locale' => 'pt-PT', 'texts' => ['Olá']],
                ['locale' => 'fr-FR', 'texts' => ['Bonjour']],
                ['locale' => 'en-GB', 'texts' => ['Hello']],
            ]],
        ]);

        $translations = $this->translator()->translate('Hello', 'en-GB', ['pt-PT', 'en-GB']);

        $this->assertSame(['pt-PT' => 'Olá'], $translations);
    }

    public function test_a_missing_locale_is_refused(): void
    {
        TranslationAgent::fake([
            ['translations' => [
                ['locale' => 'pt-PT', 'texts' => ['Olá']],
            ]],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('es-ES');

        $this->translator()->translate('Hello', 'en-GB', ['pt-PT', 'es-ES']);
    }

    public function test_blank_text_and_no_targets_never_prompt(): void
    {
        TranslationAgent::fake();

        $this->assertSame([], $this->translator()->translate('   ', 'en-GB', ['pt-PT']));
        $this->assertSame([], $this->translator()->translate('Hello', 'en-GB', ['en-GB']));

        TranslationAgent::assertNeverPrompted();
    }

    public function test_the_prompt_names_the_locales_and_carries_the_text(): void
    {
        TranslationAgent::fake([
            ['translations' => [['locale' => 'pt-PT', 'texts' => ['Olá {{{briefing.company_name}}}']]]],
        ]);

        $this->translator()->translate('Hello {{{briefing.company_name}}}', 'en-GB', ['pt-PT']);

        TranslationAgent::assertPrompted(function (AgentPrompt $prompt) {
            return $prompt->contains('Source locale: en-GB (English)')
                && $prompt->contains('- pt-PT (Portuguese)')
                && $prompt->contains('Hello {{{briefing.company_name}}}');
        });
    }

    public function test_a_list_is_translated_in_order_per_locale(): void
    {
        TranslationAgent::fake([
            ['translations' => [
                ['locale' => 'pt-PT', 'texts' => ['formal', 'casual']],
                ['locale' => 'es-ES', 'texts' => ['formal', 'informal']],
            ]],
        ]);

        $translations = $this->translator()->translateMany(['formal', 'casual'], 'en-GB', ['pt-PT', 'es-ES']);

        $this->assertSame([
            'pt-PT' => ['formal', 'casual'],
            'es-ES' => ['formal', 'informal'],
        ], $translations);

        TranslationAgent::assertPrompted(function (AgentPrompt $prompt) {
            return $prompt->contains('Texts to translate (2)')
                && $prompt->contains("1.\n```\nformal\n```")
                && $prompt->contains("2.\n```\ncasual\n```");
        });
    }

    public function test_a_list_with_the_wrong_count_is_refused(): void
    {
        TranslationAgent::fake([
            ['translations' => [
                ['locale' => 'pt-PT', 'texts' => ['formal']],
            ]],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('1 texts instead of 2');

        $this->translator()->translateMany(['formal', 'casual'], 'en-GB', ['pt-PT']);
    }

    public function test_a_list_of_blanks_never_prompts(): void
    {
        TranslationAgent::fake();

        $this->assertSame([], $this->translator()->translateMany(['', ' '], 'en-GB', ['pt-PT']));

        TranslationAgent::assertNeverPrompted();
    }
}
