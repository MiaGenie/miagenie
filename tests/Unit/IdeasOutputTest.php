<?php

namespace Tests\Unit;

use App\Enums\FunnelStage;
use App\Enums\IdeaSource;
use App\Enums\IdeaStatus;
use App\Enums\PostFormat;
use App\Genie\Ideas\IdeasOutput;
use App\Genie\Ideas\IdeasRequest;
use App\Genie\Ideas\PublishTargets;
use App\Models\AiRun;
use App\Models\AiRunStep;
use App\Models\RuleStep;
use RuntimeException;
use Tests\TestCase;

class IdeasOutputTest extends TestCase
{
    protected function ideasOutput(array $configured = ['instagram', 'youtube', 'gbp']): IdeasOutput
    {
        return new IdeasOutput(new PublishTargets($configured));
    }

    protected function runStep(IdeasRequest $request, PostFormat $format = PostFormat::CAROUSEL): AiRunStep
    {
        $run = new AiRun(['workspace_id' => 7, 'strategy_id' => 3, 'parameters' => $request->toArray()]);

        $step = new RuleStep(['post_format' => $format]);

        $runStep = new AiRunStep(['step_id' => 42]);
        $runStep->id = 99;
        $runStep->setRelation('run', $run);
        $runStep->setRelation('step', $step);

        return $runStep;
    }

    /**
     * @return array<string, mixed>
     */
    protected function idea(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Behind the build',
            'objective' => 'Show the craft',
            'suggested_content' => 'A day in the studio',
            'multimedia_suggestion' => 'Short video, handheld',
            'production_instructions' => 'Three shots, natural light',
            'funnel_stage' => 'consideration',
            'content_pillar' => 'Craft',
            'images' => ['Slide one: the workbench', 'Slide two: the tools', 'Slide three: the result'],
        ], $overrides);
    }

    public function test_each_idea_becomes_a_row_linked_to_the_run_step_and_strategy(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5));

        $rows = $this->ideasOutput()->resolve($runStep, ['ideas' => [$this->idea(), $this->idea(['title' => 'Second'])]]);

        $this->assertCount(2, $rows);

        $row = $rows[0];

        $this->assertSame(7, $row['workspace_id']);
        $this->assertSame(3, $row['strategy_id']);
        $this->assertSame(99, $row['run_step_id']);
        $this->assertSame('Behind the build', $row['theme']);
        $this->assertSame('Show the craft', $row['objective']);
        $this->assertSame('A day in the studio', $row['suggested_content']);
        $this->assertSame('Short video, handheld', $row['multimedia_suggestion']);
        $this->assertSame('Three shots, natural light', $row['production_instructions']);
        $this->assertSame(PostFormat::CAROUSEL, $row['post_format']);
        $this->assertSame(IdeaStatus::PENDING_REVIEW, $row['status']);
        $this->assertSame(IdeaSource::GENIE, $row['source']);
        $this->assertSame('Second', $rows[1]['theme']);
    }

    public function test_an_open_request_takes_the_stage_and_pillar_the_model_chose(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5));

        $row = $this->ideasOutput()->resolve($runStep, ['ideas' => [$this->idea()]])[0];

        $this->assertSame(FunnelStage::CONSIDERATION, $row['funnel_stage']);
        $this->assertSame('Craft', $row['content_pillar']);
    }

    public function test_a_fixed_request_overrides_whatever_the_model_answered(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, 'Client Stories', FunnelStage::AWARENESS, 5));

        $row = $this->ideasOutput()->resolve($runStep, ['ideas' => [$this->idea()]])[0];

        $this->assertSame(FunnelStage::AWARENESS, $row['funnel_stage']);
        $this->assertSame('Client Stories', $row['content_pillar']);
    }

    public function test_a_stage_the_enum_does_not_know_is_left_empty(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5));

        $row = $this->ideasOutput()->resolve($runStep, ['ideas' => [$this->idea(['funnel_stage' => 'decision'])]])[0];

        $this->assertNull($row['funnel_stage']);
    }

    public function test_a_response_without_a_list_of_ideas_is_refused(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5));

        $this->expectException(RuntimeException::class);

        $this->ideasOutput()->resolve($runStep, ['text' => 'nothing structured']);
    }

    public function test_an_item_that_is_not_an_object_is_skipped(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5));

        $rows = $this->ideasOutput()->resolve($runStep, ['ideas' => ['stray string', $this->idea()]]);

        $this->assertCount(1, $rows);
    }

    public function test_a_carousel_keeps_one_brief_per_slide_in_order(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::CAROUSEL);

        $row = $this->ideasOutput()->resolve($runStep, ['ideas' => [$this->idea()]])[0];

        $this->assertSame(
            ['Slide one: the workbench', 'Slide two: the tools', 'Slide three: the result'],
            $row['images'],
        );
    }

    public function test_blank_and_non_text_briefs_are_dropped_and_the_list_reindexed(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::CAROUSEL);

        $row = $this->ideasOutput()->resolve($runStep, ['ideas' => [
            $this->idea(['images' => ['First', '', null, ['nested'], 'Last']]),
        ]])[0];

        $this->assertSame(['First', 'Last'], $row['images']);
    }

    public function test_a_simple_post_keeps_its_one_brief(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::SIMPLE_POST);

        $row = $this->ideasOutput()->resolve($runStep, ['ideas' => [$this->idea(['images' => ['The hero shot']])]])[0];

        $this->assertSame(['The hero shot'], $row['images']);
    }

    public function test_a_video_stores_no_briefs_even_if_the_model_sent_some(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::LONG_VIDEO);

        $row = $this->ideasOutput()->resolve($runStep, ['ideas' => [$this->idea()]])[0];

        $this->assertNull($row['images']);
    }

    public function test_a_generated_idea_targets_every_configured_provider_that_accepts_its_format(): void
    {
        $carousel = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::CAROUSEL);
        $video = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::LONG_VIDEO);

        $this->assertSame(['instagram'], $this->ideasOutput()->resolve($carousel, ['ideas' => [$this->idea()]])[0]['publish_to']);
        $this->assertSame(['youtube'], $this->ideasOutput()->resolve($video, ['ideas' => [$this->idea()]])[0]['publish_to']);
    }

    public function test_no_matching_account_stores_no_targets(): void
    {
        $runStep = $this->runStep(new IdeasRequest(null, null, FunnelStage::GLOBAL, 5), PostFormat::CAROUSEL);

        $row = $this->ideasOutput(['youtube'])->resolve($runStep, ['ideas' => [$this->idea()]])[0];

        $this->assertNull($row['publish_to']);
    }
}
