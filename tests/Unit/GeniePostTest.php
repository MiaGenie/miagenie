<?php

namespace Tests\Unit;

use App\Enums\PostFormat;
use App\Enums\RuleSubType;
use App\Models\AiRun;
use App\Models\GeniePost;
use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GeniePostTest extends TestCase
{
    protected function geniePost(PostFormat $format, array $steps): GeniePost
    {
        $rule = new Rule;
        $rule->setRelation('steps', new Collection($steps));

        $run = new AiRun;
        $run->setRelation('rule', $rule);

        $post = new GeniePost(['post_format' => $format]);
        $post->setRelation('run', $run);

        return $post;
    }

    protected function step(RuleSubType $subType, ?PostFormat $format, int $position): RuleStep
    {
        $step = new RuleStep(['rule_sub_type' => $subType, 'post_format' => $format, 'position' => $position]);
        $step->id = $position;

        return $step;
    }

    public function test_revision_templates_follow_the_format_override_rule(): void
    {
        $genericFix = $this->step(RuleSubType::POSTS_IMAGE_FIX, null, 1);
        $carouselFix = $this->step(RuleSubType::POSTS_IMAGE_FIX, PostFormat::CAROUSEL, 2);
        $revise = $this->step(RuleSubType::POSTS_REVISE_TEXT, null, 3);

        $carousel = $this->geniePost(PostFormat::CAROUSEL, [$genericFix, $carouselFix, $revise]);
        $this->assertSame($carouselFix, $carousel->stepFor(RuleSubType::POSTS_IMAGE_FIX));
        $this->assertSame($revise, $carousel->stepFor(RuleSubType::POSTS_REVISE_TEXT));
        $this->assertNull($carousel->stepFor(RuleSubType::POSTS_IMAGE_RETHINK));

        $simple = $this->geniePost(PostFormat::SIMPLE_POST, [$genericFix, $carouselFix, $revise]);
        $this->assertSame($genericFix, $simple->stepFor(RuleSubType::POSTS_IMAGE_FIX));

        $this->assertSame(
            ['revise_text' => true, 'fix' => true, 'rethink' => false, 'generate_images' => false],
            $simple->actions(),
        );
    }

    public function test_generate_images_is_offered_only_before_any_slot_exists_on_an_image_format(): void
    {
        $image = $this->step(RuleSubType::POSTS_IMAGE, null, 1);

        $empty = $this->geniePost(PostFormat::CAROUSEL, [$image]);
        $this->assertTrue($empty->actions()['generate_images']);

        $drawn = $this->geniePost(PostFormat::CAROUSEL, [$image]);
        $drawn->images = [['position' => 1, 'file_id' => 4, 'media_id' => '11']];
        $this->assertFalse($drawn->actions()['generate_images']);

        $video = $this->geniePost(PostFormat::SHORT_VIDEO, [$image]);
        $this->assertFalse($video->actions()['generate_images']);

        $noStep = $this->geniePost(PostFormat::CAROUSEL, []);
        $this->assertFalse($noStep->actions()['generate_images']);
    }

    public function test_a_post_whose_rule_is_gone_offers_no_actions(): void
    {
        $post = new GeniePost(['post_format' => PostFormat::SIMPLE_POST]);
        $post->setRelation('run', null);

        $this->assertSame(['revise_text' => false, 'fix' => false, 'rethink' => false, 'generate_images' => false], $post->actions());
    }
}
