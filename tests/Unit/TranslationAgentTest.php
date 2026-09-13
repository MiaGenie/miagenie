<?php

namespace Tests\Unit;

use App\Ai\Agents\TranslationAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;
use Tests\TestCase;

class TranslationAgentTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function compile(): array
    {
        $agent = new TranslationAgent;

        return new ObjectSchema($agent->schema(new JsonSchemaTypeFactory), 'translations', strict: true)->toSchema();
    }

    public function test_the_schema_is_a_list_of_locales_each_with_ordered_texts(): void
    {
        $schema = $this->compile();

        $this->assertSame(['translations'], $schema['required']);

        $items = $schema['properties']['translations']['items'];

        $this->assertSame('object', $items['type']);
        $this->assertSame(['locale', 'texts'], array_keys($items['properties']));
        $this->assertSame(['locale', 'texts'], $items['required']);
        $this->assertSame('array', $items['properties']['texts']['type']);
        $this->assertSame('string', $items['properties']['texts']['items']['type']);
        $this->assertFalse($items['additionalProperties']);
    }

    public function test_the_instructions_protect_markup_and_placeholders(): void
    {
        $instructions = (string) (new TranslationAgent)->instructions();

        $this->assertStringContainsString('{{{', $instructions);
        $this->assertStringContainsString('same order', $instructions);
        $this->assertStringContainsString('HTML', $instructions);
        $this->assertStringContainsString('Markdown', $instructions);
    }
}
