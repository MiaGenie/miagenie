<?php

namespace Tests\Unit;

use App\Enums\SubFieldType;
use App\Genie\Strategy\StrategyContext;
use App\Models\AiRun;
use App\Models\Rule;
use App\Models\Strategy;
use App\Models\Version;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StrategyContextTest extends TestCase
{
    /**
     * Ids are handed out by the test rather than the database, because the renderer matches a
     * sub-field's children by parent_id exactly as StepSchemaBuilder::buildType() does.
     */
    protected int $nextSubFieldId = 1;

    /**
     * A run with its version's strategy fields and the strategy written so far already attached,
     * so the block can be rendered without touching the database.
     *
     * @param  array<int, VersionField>  $fields
     * @param  array<string, mixed>  $content
     */
    protected function runWith(array $fields, array $content): AiRun
    {
        $version = new Version(['name' => 'Test']);
        $version->setRelation('strategies', new Collection($fields));

        $rule = new Rule;
        $rule->setRelation('version', $version);

        $run = new AiRun;
        $run->setRelation('rule', $rule);
        $run->setRelation('strategy', new Strategy(['content' => $content]));

        return $run;
    }

    /**
     * A strategy field whose sub-field tree is the given roots, flattened with their descendants.
     *
     * @param  array<int, VersionFieldSubField>  $subFields
     */
    protected function field(string $codeName, string $label, array $subFields = []): VersionField
    {
        $field = new VersionField([
            'code_name' => $codeName,
            'name' => ['en-GB' => $label],
        ]);

        $field->setRelation('subFields', new Collection($subFields));

        return $field;
    }

    /**
     * One sub-field, with its children flattened into the same list the field carries.
     *
     * @param  array<int, VersionFieldSubField>  $children
     * @return array<int, VersionFieldSubField> the sub-field followed by its descendants
     */
    protected function sub(
        string $subCodeName,
        string $label,
        SubFieldType $type = SubFieldType::STRING,
        array $children = [],
        int $position = 0,
    ): array {
        $subField = new VersionFieldSubField([
            'sub_code_name' => $subCodeName,
            'name' => ['en-GB' => $label],
            'type' => $type,
            'position' => $position,
        ]);

        $subField->id = $this->nextSubFieldId++;

        $descendants = [];

        foreach ($children as $child) {
            foreach ($child as $node) {
                if ($node->parent_id === null) {
                    $node->parent_id = $subField->id;
                }

                $descendants[] = $node;
            }
        }

        return array_merge([$subField], $descendants);
    }

    /**
     * @param  array<int, array<int, VersionFieldSubField>>  $subFields
     * @return array<int, VersionFieldSubField>
     */
    protected function tree(array $subFields): array
    {
        return array_merge(...$subFields);
    }

    protected function full(array $fields, array $content): string
    {
        return (new StrategyContext($this->runWith($fields, $content), 'en-GB'))->full();
    }

    /**
     * @return array<string, string>
     */
    protected function answers(array $fields, array $content): array
    {
        return (new StrategyContext($this->runWith($fields, $content), 'en-GB'))->answers();
    }

    public function test_each_field_is_a_labelled_block_in_the_order_the_version_defines(): void
    {
        $block = $this->full(
            [
                $this->field('positioning', 'Positioning', $this->sub('positioning', 'Positioning')),
                $this->field('tone_of_voice', 'Tone of Voice', $this->sub('tone_of_voice', 'Tone of Voice')),
            ],
            // Stored the other way round: the fields decide the reading order, not the JSON.
            ['tone_of_voice' => 'Warm and direct', 'positioning' => 'The studio for founders'],
        );

        $this->assertSame(
            "**Positioning**\nThe studio for founders\n\n**Tone of Voice**\nWarm and direct",
            $block,
        );
    }

    public function test_a_single_root_array_of_objects_renders_as_bullets(): void
    {
        $block = $this->full(
            [$this->field('content_pillars', 'Content Pillars', $this->tree([
                $this->sub('pillars', 'Pillars', SubFieldType::ARRAY, [
                    $this->sub('pillar', 'Pillar', position: 0),
                    $this->sub('description', 'Description', position: 1),
                    $this->sub('formats', 'Formats', SubFieldType::ARRAY, position: 2),
                ]),
            ]))],
            ['content_pillars' => [
                [
                    'pillar' => 'Behind the scenes',
                    'description' => 'Show the studio at work',
                    'formats' => ['Reel', 'Carousel'],
                ],
                [
                    'pillar' => 'Client stories',
                    'description' => 'Short case studies',
                    'formats' => ['Carousel'],
                ],
            ]],
        );

        $this->assertSame(
            "**Content Pillars**\n"
            ."- **Pillar**: Behind the scenes\n"
            ."  **Description**: Show the studio at work\n"
            ."  **Formats**: Reel, Carousel\n"
            ."- **Pillar**: Client stories\n"
            ."  **Description**: Short case studies\n"
            .'  **Formats**: Carousel',
            $block,
        );
    }

    public function test_several_roots_are_an_implicit_object(): void
    {
        $block = $this->full(
            [$this->field('audience', 'Audience', $this->tree([
                $this->sub('primary', 'Primary', position: 0),
                $this->sub('secondary', 'Secondary', position: 1),
            ]))],
            ['audience' => ['primary' => 'Founders', 'secondary' => 'Marketing leads']],
        );

        $this->assertSame(
            "**Audience**\n**Primary**: Founders\n**Secondary**: Marketing leads",
            $block,
        );
    }

    public function test_a_nested_object_is_indented_under_its_label(): void
    {
        $block = $this->full(
            [$this->field('brand', 'Brand', $this->tree([
                $this->sub('brand', 'Brand', SubFieldType::OBJECT, [
                    $this->sub('promise', 'Promise', position: 0),
                    $this->sub('voice', 'Voice', SubFieldType::OBJECT, [
                        $this->sub('tone', 'Tone', position: 0),
                        $this->sub('avoid', 'Avoid', position: 1),
                    ], position: 1),
                ]),
            ]))],
            ['brand' => [
                'promise' => 'Ship in weeks',
                'voice' => ['tone' => 'Plain', 'avoid' => 'Hype'],
            ]],
        );

        $this->assertSame(
            "**Brand**\n"
            ."**Promise**: Ship in weeks\n"
            ."**Voice**\n"
            ."  **Tone**: Plain\n"
            .'  **Avoid**: Hype',
            $block,
        );
    }

    public function test_an_array_without_children_is_a_joined_list(): void
    {
        $block = $this->full(
            [$this->field('channels', 'Channels', $this->sub('channels', 'Channels', SubFieldType::ARRAY))],
            ['channels' => ['Instagram', 'LinkedIn', 'TikTok']],
        );

        $this->assertSame("**Channels**\nInstagram, LinkedIn, TikTok", $block);
    }

    public function test_a_boolean_is_read_in_words(): void
    {
        $fields = [$this->field('paid_media', 'Paid Media', $this->tree([
            $this->sub('runs_ads', 'Runs Ads', SubFieldType::BOOLEAN, position: 0),
            $this->sub('has_budget', 'Has Budget', SubFieldType::BOOLEAN, position: 1),
        ]))];

        $block = $this->full($fields, ['paid_media' => ['runs_ads' => true, 'has_budget' => false]]);

        $this->assertSame("**Paid Media**\n**Runs Ads**: Yes\n**Has Budget**: No", $block);
    }

    public function test_blank_sub_field_values_are_left_out(): void
    {
        // StepSchemaBuilder::decorate() makes an optional sub-field nullable-but-present, so an
        // unwritten key arrives as null rather than missing.
        $block = $this->full(
            [$this->field('audience', 'Audience', $this->tree([
                $this->sub('primary', 'Primary', position: 0),
                $this->sub('secondary', 'Secondary', position: 1),
                $this->sub('notes', 'Notes', position: 2),
            ]))],
            ['audience' => ['primary' => 'Founders', 'secondary' => null, 'notes' => '']],
        );

        $this->assertSame("**Audience**\n**Primary**: Founders", $block);
    }

    public function test_a_field_with_no_value_produces_no_block_at_all(): void
    {
        $block = $this->full(
            [
                $this->field('positioning', 'Positioning', $this->sub('positioning', 'Positioning')),
                $this->field('not_written_yet', 'Not Written Yet', $this->sub('not_written_yet', 'Not Written Yet')),
                $this->field('emptied', 'Emptied', $this->sub('emptied', 'Emptied', SubFieldType::ARRAY)),
            ],
            ['positioning' => 'The studio for founders', 'emptied' => []],
        );

        $this->assertSame("**Positioning**\nThe studio for founders", $block);
    }

    public function test_a_sub_key_the_tree_no_longer_has_falls_back_to_its_raw_key(): void
    {
        // A strategy outlives the tree it was written against.
        $block = $this->full(
            [$this->field('audience', 'Audience', $this->tree([
                $this->sub('primary', 'Primary', position: 0),
                $this->sub('secondary', 'Secondary', position: 1),
            ]))],
            ['audience' => ['primary' => 'Founders', 'tertiary' => 'Agencies']],
        );

        $this->assertSame("**Audience**\n**Primary**: Founders\n**tertiary**: Agencies", $block);
    }

    public function test_answers_are_keyed_by_code_name_without_the_field_label(): void
    {
        $answers = $this->answers(
            [
                $this->field('positioning', 'Positioning', $this->sub('positioning', 'Positioning')),
                $this->field('content_pillars', 'Content Pillars', $this->tree([
                    $this->sub('pillars', 'Pillars', SubFieldType::ARRAY, [
                        $this->sub('pillar', 'Pillar', position: 0),
                        $this->sub('formats', 'Formats', SubFieldType::ARRAY, position: 1),
                    ]),
                ])),
            ],
            [
                'positioning' => 'The studio for founders',
                'content_pillars' => [
                    ['pillar' => 'Behind the scenes', 'formats' => ['Reel', 'Carousel']],
                ],
            ],
        );

        $this->assertSame([
            'positioning' => 'The studio for founders',
            'content_pillars' => "- **Pillar**: Behind the scenes\n  **Formats**: Reel, Carousel",
        ], $answers);
    }

    public function test_a_value_whose_field_is_gone_still_produces_a_replacement(): void
    {
        // A message written against an older version keeps resolving: the value is read
        // structurally from its own keys.
        $answers = $this->answers([], [
            'retired_text' => 'Kept',
            'retired_object' => ['tone' => 'Plain', 'channels' => ['Instagram', 'LinkedIn']],
            'retired_list' => ['Instagram', 'LinkedIn'],
            'retired_items' => [['pillar' => 'Behind the scenes'], ['pillar' => 'Client stories']],
        ]);

        $this->assertSame([
            'retired_text' => 'Kept',
            // A list of scalars reads beside its key, as it would with a sub-field to describe it.
            'retired_object' => "**tone**: Plain\n**channels**: Instagram, LinkedIn",
            'retired_list' => 'Instagram, LinkedIn',
            'retired_items' => "- **pillar**: Behind the scenes\n- **pillar**: Client stories",
        ], $answers);
    }

    public function test_a_run_without_a_rule_renders_nothing_rather_than_failing(): void
    {
        $run = new AiRun;
        $run->setRelation('rule', null);
        $run->setRelation('strategy', new Strategy(['content' => ['positioning' => 'Kept']]));

        $context = new StrategyContext($run, 'en-GB');

        $this->assertSame('', $context->full());
        $this->assertSame(['positioning' => 'Kept'], $context->answers());
    }

    public function test_a_run_without_a_strategy_renders_nothing_rather_than_failing(): void
    {
        $run = $this->runWith([$this->field('positioning', 'Positioning', $this->sub('positioning', 'Positioning'))], []);
        $run->setRelation('strategy', null);

        $context = new StrategyContext($run, 'en-GB');

        $this->assertSame('', $context->full());
        $this->assertSame([], $context->answers());
    }
}
