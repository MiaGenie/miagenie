<?php

namespace Tests\Unit;

use App\Enums\Modality;
use App\Enums\RuleSubType;
use Tests\TestCase;

class RuleSubTypeTest extends TestCase
{
    public function test_the_initial_steps_are_context_steps(): void
    {
        $this->assertTrue(RuleSubType::IDEAS_INITIAL->isContext());

        $this->assertFalse(RuleSubType::IDEAS_FORMAT->isContext());
        $this->assertFalse(RuleSubType::BRIEFINGS->isContext());
        $this->assertFalse(RuleSubType::CHANNELS->isContext());
    }

    public function test_only_a_format_step_generates(): void
    {
        $this->assertTrue(RuleSubType::IDEAS_FORMAT->isGenerator());
        $this->assertTrue(RuleSubType::POSTS_CONTENT->isGenerator());
        $this->assertTrue(RuleSubType::POSTS_IMAGE->isGenerator());
        $this->assertTrue(RuleSubType::POSTS_REVISE_TEXT->isGenerator());

        $this->assertFalse(RuleSubType::IDEAS_INITIAL->isGenerator());
        $this->assertFalse(RuleSubType::IDEAS_MULTIPLE->isGenerator());
        $this->assertFalse(RuleSubType::BRIEFINGS->isGenerator());
    }

    public function test_only_an_ideas_step_must_name_its_post_format(): void
    {
        $this->assertTrue(RuleSubType::IDEAS_FORMAT->requiresPostFormat());
        $this->assertFalse(RuleSubType::POSTS_CONTENT->requiresPostFormat());
        $this->assertFalse(RuleSubType::POSTS_IMAGE->requiresPostFormat());
    }

    public function test_the_posts_image_sub_types_draw_and_everything_else_writes(): void
    {
        foreach ([RuleSubType::POSTS_IMAGE, RuleSubType::POSTS_IMAGE_FIX, RuleSubType::POSTS_IMAGE_RETHINK] as $subType) {
            $this->assertSame(Modality::IMAGE, $subType->modality(), $subType->name);
        }

        foreach ([RuleSubType::POSTS_CONTENT, RuleSubType::POSTS_REVISE_TEXT, RuleSubType::IDEAS_FORMAT, RuleSubType::IDEAS_INITIAL, RuleSubType::BRIEFINGS] as $subType) {
            $this->assertSame(Modality::TEXT, $subType->modality(), $subType->name);
        }
    }

    public function test_only_three_posts_sub_types_run_in_a_posts_run(): void
    {
        $this->assertTrue(RuleSubType::POSTS_CONTENT->runsInPostsRun());
        $this->assertTrue(RuleSubType::POSTS_IMAGE->runsInPostsRun());
        $this->assertTrue(RuleSubType::POSTS_REVISE_TEXT->runsInPostsRun());
        $this->assertFalse(RuleSubType::POSTS_IMAGE_FIX->runsInPostsRun());
        $this->assertFalse(RuleSubType::POSTS_IMAGE_RETHINK->runsInPostsRun());
        $this->assertFalse(RuleSubType::IDEAS_FORMAT->runsInPostsRun());
    }

    public function test_the_editor_rows_carry_the_generator_flags(): void
    {
        $rows = RuleSubType::forEditor(5)->keyBy('name');

        $this->assertSame(['POSTS_CONTENT', 'POSTS_IMAGE', 'POSTS_REVISE_TEXT', 'POSTS_IMAGE_FIX', 'POSTS_IMAGE_RETHINK'], $rows->keys()->all());
        $this->assertTrue($rows['POSTS_CONTENT']['generator']);
        $this->assertFalse($rows['POSTS_CONTENT']['format_required']);
        $this->assertFalse($rows['POSTS_CONTENT']['image']);
        $this->assertTrue($rows['POSTS_IMAGE']['image']);
        $this->assertTrue($rows['POSTS_IMAGE_FIX']['image']);

        $ideas = RuleSubType::forEditor(2)->keyBy('name');

        $this->assertTrue($ideas['IDEAS_FORMAT']['format_required']);
    }
}
