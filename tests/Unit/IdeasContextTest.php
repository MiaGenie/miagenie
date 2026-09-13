<?php

namespace Tests\Unit;

use App\Enums\FunnelStage;
use App\Enums\PostFormat;
use App\Enums\SubFieldType;
use App\Genie\Ideas\IdeasContext;
use App\Genie\Ideas\IdeasRequest;
use App\Models\AiRun;
use App\Models\Rule;
use App\Models\RuleStep;
use App\Models\Strategy;
use App\Models\Version;
use App\Models\VersionField;
use App\Models\VersionFieldSubField;
use Illuminate\Support\Collection;
use Tests\TestCase;

class IdeasContextTest extends TestCase
{
    protected int $nextSubFieldId = 1;

    /**
     * A run whose version defines the content_pillars field the way version 5 does — an array of
     * objects with a title, an importance, a style, a description and a list of topics — with
     * two pillars written on the strategy.
     */
    protected function runWithPillars(array $content = []): AiRun
    {
        $topics = $this->sub('topics', 'Topics & Subtopics', SubFieldType::ARRAY, [
            $this->sub('topic_name', 'Topic', SubFieldType::STRING, [], 0),
            $this->sub('topic_subtopics', 'Sub Topics', SubFieldType::ARRAY, [], 1),
        ], 4);

        $pillars = $this->sub('content_pillars', 'Content Pillars', SubFieldType::ARRAY, [
            $this->sub('title', 'Title', SubFieldType::STRING, [], 0),
            $this->sub('importance', 'Relative Importance', SubFieldType::STRING, [], 1),
            $this->sub('description', 'Description', SubFieldType::STRING, [], 3),
            $topics,
        ]);

        $field = new VersionField(['code_name' => 'content_pillars', 'name' => ['en-GB' => 'Content Pillars']]);
        $field->setRelation('subFields', new Collection($pillars));

        $version = new Version(['name' => 'Test']);
        $version->setRelation('strategies', new Collection([$field]));

        $rule = new Rule;
        $rule->setRelation('version', $version);

        $run = new AiRun;
        $run->setRelation('rule', $rule);
        $run->setRelation('strategy', new Strategy(['content' => $content ?: [
            'content_pillars' => [
                [
                    'title' => 'Craft',
                    'importance' => 'High',
                    'description' => 'How things are made',
                    'topics' => [['topic_name' => 'Tools', 'topic_subtopics' => ['Chisels', 'Planes']]],
                ],
                [
                    'title' => 'Stories',
                    'importance' => 'Medium',
                    'description' => 'Who we made them for',
                    'topics' => [['topic_name' => 'Clients', 'topic_subtopics' => ['Homes']]],
                ],
            ],
        ]]));

        return $run;
    }

    /**
     * @return array<int, VersionFieldSubField>
     */
    protected function sub(string $subCodeName, string $label, SubFieldType $type, array $children = [], int $position = 0): array
    {
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

    protected function context(AiRun $run, IdeasRequest $request, ?PostFormat $format = PostFormat::CAROUSEL, int $share = 3): IdeasContext
    {
        return new IdeasContext($run, new RuleStep(['post_format' => $format]), $request, $share, 'en-GB');
    }

    public function test_a_requested_pillar_is_rendered_with_everything_the_strategy_says_about_it(): void
    {
        $context = $this->context($this->runWithPillars(), new IdeasRequest(null, 'Stories', FunnelStage::GLOBAL, 5));

        $pillar = $context->pillar();

        $this->assertStringContainsString('**Title**: Stories', $pillar);
        $this->assertStringContainsString('**Relative Importance**: Medium', $pillar);
        $this->assertStringContainsString('**Description**: Who we made them for', $pillar);
        $this->assertStringContainsString('**Topic**: Clients', $pillar);
        $this->assertStringContainsString('**Sub Topics**: Homes', $pillar);
        $this->assertStringNotContainsString('Craft', $pillar);
    }

    public function test_any_pillar_renders_every_pillar(): void
    {
        $context = $this->context($this->runWithPillars(), new IdeasRequest(null, null, FunnelStage::GLOBAL, 5));

        $pillar = $context->pillar();

        $this->assertStringContainsString('Craft', $pillar);
        $this->assertStringContainsString('Stories', $pillar);
    }

    public function test_a_pillar_the_strategy_no_longer_has_falls_back_to_its_title(): void
    {
        $context = $this->context($this->runWithPillars(), new IdeasRequest(null, 'Gone', FunnelStage::GLOBAL, 5));

        $this->assertSame('Gone', $context->pillar());
    }

    public function test_the_funnel_is_the_stage_label_or_any_stage(): void
    {
        $run = $this->runWithPillars();

        $this->assertSame(
            __('genie.funnel_stage_conversion'),
            $this->context($run, new IdeasRequest(null, null, FunnelStage::CONVERSION, 5))->funnel(),
        );

        $this->assertSame(
            __('genie.ideas_any_stage'),
            $this->context($run, new IdeasRequest(null, null, FunnelStage::GLOBAL, 5))->funnel(),
        );
    }

    public function test_the_format_is_the_steps_own_or_any_for_a_context_step(): void
    {
        $run = $this->runWithPillars();
        $request = new IdeasRequest(null, null, FunnelStage::GLOBAL, 5);

        $this->assertSame(__('genie.post_format_carousel'), $this->context($run, $request, PostFormat::CAROUSEL)->format());
        $this->assertSame(__('genie.post_format_any'), $this->context($run, $request, null)->format());
    }

    public function test_channels_are_the_chosen_ones_that_carry_the_format(): void
    {
        $chosen = ['facebook' => true, 'youtube' => true, 'website' => true, 'tiktok' => 'false', 'gbp' => true];

        $this->assertSame(['facebook_page', 'youtube'], IdeasContext::channelsFor($chosen, PostFormat::LONG_VIDEO));
        $this->assertSame(['facebook_page', 'gbp'], IdeasContext::channelsFor($chosen, PostFormat::SIMPLE_POST));
        $this->assertSame(['facebook_page', 'youtube', 'gbp'], IdeasContext::channelsFor($chosen, null));
    }

    public function test_channels_are_rendered_as_readable_names(): void
    {
        $run = $this->runWithPillars([
            'content_pillars' => [],
            'digital_channels' => ['instagram' => true, 'facebook' => true, 'youtube' => false],
        ]);

        $context = $this->context($run, new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::SHORT_VIDEO);

        $this->assertSame('Instagram, Facebook Page', $context->channels());
    }
}
