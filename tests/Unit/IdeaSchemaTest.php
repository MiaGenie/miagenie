<?php

namespace Tests\Unit;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
use App\Genie\Ideas\IdeaSchema;
use App\Genie\Ideas\IdeasRequest;
use App\Models\AiRun;
use App\Models\Strategy;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;
use Tests\TestCase;

class IdeaSchemaTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function compile(IdeaSchema $schema): array
    {
        return new ObjectSchema($schema->properties(new JsonSchemaTypeFactory), 'ideas', strict: true)->toSchema();
    }

    public function test_the_root_is_a_list_of_exactly_the_share(): void
    {
        $schema = $this->compile(new IdeaSchema(FunnelStage::GLOBAL, ['A', 'B'], 3, 'en-GB'));

        $ideas = $schema['properties']['ideas'];

        $this->assertSame(['ideas'], $schema['required']);
        $this->assertSame('array', $ideas['type']);
        $this->assertSame(3, $ideas['minItems']);
        $this->assertSame(3, $ideas['maxItems']);
    }

    public function test_every_idea_property_is_required_for_strict_mode(): void
    {
        $schema = $this->compile(new IdeaSchema(FunnelStage::GLOBAL, ['A'], 1, 'en-GB'));

        $item = $schema['properties']['ideas']['items'];

        $this->assertSame(
            ['title', 'objective', 'suggested_content', 'multimedia_suggestion', 'production_instructions', 'funnel_stage', 'content_pillar'],
            array_keys($item['properties']),
        );
        $this->assertSame(array_keys($item['properties']), $item['required']);
        $this->assertFalse($item['additionalProperties']);
    }

    public function test_any_stage_offers_every_concrete_stage(): void
    {
        $schema = $this->compile(new IdeaSchema(FunnelStage::GLOBAL, ['A'], 1, 'en-GB'));

        $this->assertSame(
            ['awareness', 'problem_awareness', 'consideration', 'conversion', 'repurchase'],
            $schema['properties']['ideas']['items']['properties']['funnel_stage']['enum'],
        );
    }

    public function test_a_fixed_stage_offers_only_itself(): void
    {
        $schema = $this->compile(new IdeaSchema(FunnelStage::CONVERSION, ['A'], 1, 'en-GB'));

        $this->assertSame(
            ['conversion'],
            $schema['properties']['ideas']['items']['properties']['funnel_stage']['enum'],
        );
    }

    public function test_the_pillars_offered_are_the_titles_given(): void
    {
        $schema = $this->compile(new IdeaSchema(FunnelStage::GLOBAL, ['Client Stories', 'Tech'], 1, 'en-GB'));

        $pillar = $schema['properties']['ideas']['items']['properties']['content_pillar'];

        $this->assertSame(['Client Stories', 'Tech'], $pillar['enum']);
        $this->assertSame('string', $pillar['type']);
    }

    public function test_without_pillars_the_pillar_is_free_text_and_nullable(): void
    {
        $schema = $this->compile(new IdeaSchema(FunnelStage::GLOBAL, [], 1, 'en-GB'));

        $pillar = $schema['properties']['ideas']['items']['properties']['content_pillar'];

        $this->assertArrayNotHasKey('enum', $pillar);
        $this->assertContains('null', (array) $pillar['type']);
    }

    public function test_the_descriptions_are_the_field_descriptions(): void
    {
        $schema = $this->compile(new IdeaSchema(FunnelStage::GLOBAL, [], 1, 'en-GB'));

        $properties = $schema['properties']['ideas']['items']['properties'];

        $this->assertSame(__('genie.idea_objective_desc'), $properties['objective']['description']);
        $this->assertSame(__('genie.idea_production_instructions'), $properties['production_instructions']['title']);
    }

    public function test_for_a_run_it_takes_the_requested_pillar_or_all_of_the_strategys(): void
    {
        $run = new AiRun;
        $run->setRelation('strategy', new Strategy(['content' => [
            'content_pillars' => [
                ['0_title' => 'First', '1_importance' => 'High'],
                ['0_title' => 'Second', '1_importance' => 'Low'],
            ],
        ]]));

        $all = $this->compile(IdeaSchema::forRun(
            $run,
            new IdeasRequest(PostFormat::CAROUSEL, null, FunnelStage::GLOBAL, 10),
            2,
            'en-GB',
        ));

        $this->assertSame(['First', 'Second'], $all['properties']['ideas']['items']['properties']['content_pillar']['enum']);

        $one = $this->compile(IdeaSchema::forRun(
            $run,
            new IdeasRequest(PostFormat::CAROUSEL, 'Second', FunnelStage::AWARENESS, 10),
            2,
            'en-GB',
        ));

        $this->assertSame(['Second'], $one['properties']['ideas']['items']['properties']['content_pillar']['enum']);
        $this->assertSame(['awareness'], $one['properties']['ideas']['items']['properties']['funnel_stage']['enum']);
    }

    public function test_a_preview_asks_for_one_idea_of_any_stage_and_pillar(): void
    {
        $schema = $this->compile(IdeaSchema::preview('en-GB'));

        $this->assertSame(1, $schema['properties']['ideas']['minItems']);
        $this->assertArrayNotHasKey('enum', $schema['properties']['ideas']['items']['properties']['content_pillar']);
    }
}
