<?php

namespace Tests\Unit;

use App\Enums\PostFormat;
use App\Enums\RuleSubType;
use App\Genie\Posts\PostsRequest;
use App\Genie\Posts\PostsStepGate;
use App\Models\AiRun;
use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PostsStepGateTest extends TestCase
{
    protected PostsStepGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = new PostsStepGate;
    }

    protected function step(RuleSubType $subType, ?PostFormat $format, int $position): RuleStep
    {
        $step = new RuleStep([
            'name' => $subType->name,
            'rule_sub_type' => $subType,
            'modality' => $subType->modality(),
            'post_format' => $format,
            'position' => $position,
        ]);

        $step->id = $position;

        return $step;
    }

    /**
     * A full posts rule: every sub type once, no formats.
     *
     * @return array<string, RuleStep>
     */
    protected function fullRule(): array
    {
        return [
            'content' => $this->step(RuleSubType::POSTS_CONTENT, null, 1),
            'image' => $this->step(RuleSubType::POSTS_IMAGE, null, 2),
            'revise' => $this->step(RuleSubType::POSTS_REVISE_TEXT, null, 3),
            'fix' => $this->step(RuleSubType::POSTS_IMAGE_FIX, null, 4),
            'rethink' => $this->step(RuleSubType::POSTS_IMAGE_RETHINK, null, 5),
        ];
    }

    protected function runFor(PostFormat $format, ?string $only, array $steps, ?string $feedback = null, bool $images = true): AiRun
    {
        $run = new AiRun(['parameters' => (new PostsRequest(1, 2, $format, $only, $feedback, $images))->toArray()]);

        $rule = new Rule;
        $rule->setRelation('steps', new Collection(array_values($steps)));
        $run->setRelation('rule', $rule);

        return $run;
    }

    public function test_a_first_generation_runs_the_content_and_image_steps_only(): void
    {
        $steps = $this->fullRule();
        $run = $this->runFor(PostFormat::SIMPLE_POST, null, $steps);

        $this->assertSame([1, 2], $this->gate->generators($run)->pluck('id')->all());
        $this->assertFalse($this->gate->passes($run, $steps['revise']));
        $this->assertFalse($this->gate->passes($run, $steps['fix']));
        $this->assertFalse($this->gate->passes($run, $steps['rethink']));
    }

    public function test_a_first_run_without_images_runs_the_content_step_only_and_images_can_follow(): void
    {
        $steps = $this->fullRule();

        $first = $this->runFor(PostFormat::CAROUSEL, null, $steps, null, false);
        $this->assertSame([1], $this->gate->generators($first)->pluck('id')->all());

        $later = $this->runFor(PostFormat::CAROUSEL, PostsRequest::ONLY_IMAGES, $steps);
        $this->assertSame([2], $this->gate->generators($later)->pluck('id')->all());
    }

    public function test_a_caption_regeneration_runs_the_revise_text_step_alone(): void
    {
        $steps = $this->fullRule();
        $run = $this->runFor(PostFormat::SIMPLE_POST, PostsRequest::ONLY_TEXT, $steps, 'Shorter');

        $this->assertSame([3], $this->gate->generators($run)->pluck('id')->all());
        $this->assertFalse($this->gate->passes($run, $steps['content']));
        $this->assertFalse($this->gate->passes($run, $steps['image']));
    }

    public function test_an_images_only_run_runs_the_image_step_alone(): void
    {
        $steps = $this->fullRule();
        $run = $this->runFor(PostFormat::CAROUSEL, PostsRequest::ONLY_IMAGES, $steps);

        $this->assertSame([2], $this->gate->generators($run)->pluck('id')->all());
    }

    public function test_there_is_no_fallback_when_the_revise_step_is_missing(): void
    {
        $steps = $this->fullRule();
        unset($steps['revise']);

        $run = $this->runFor(PostFormat::SIMPLE_POST, PostsRequest::ONLY_TEXT, $steps, 'Shorter');

        $this->assertTrue($this->gate->generators($run)->isEmpty());
    }

    public function test_a_video_idea_runs_no_image_step(): void
    {
        $steps = $this->fullRule();
        $run = $this->runFor(PostFormat::SHORT_VIDEO, null, $steps);

        $this->assertSame([1], $this->gate->generators($run)->pluck('id')->all());
    }

    public function test_a_format_specific_step_wins_over_the_generic_one_and_only_one_runs(): void
    {
        $carousel = $this->step(RuleSubType::POSTS_CONTENT, PostFormat::CAROUSEL, 1);
        $any = $this->step(RuleSubType::POSTS_CONTENT, null, 2);

        $simple = $this->runFor(PostFormat::SIMPLE_POST, null, [$carousel, $any]);
        $this->assertSame([2], $this->gate->generators($simple)->pluck('id')->all());

        $carouselRun = $this->runFor(PostFormat::CAROUSEL, null, [$carousel, $any]);
        $this->assertSame([1], $this->gate->generators($carouselRun)->pluck('id')->all());
        $this->assertFalse($this->gate->passes($carouselRun, $any));
    }

    public function test_two_generic_steps_of_one_sub_type_resolve_to_the_first_by_position(): void
    {
        $second = $this->step(RuleSubType::POSTS_CONTENT, null, 5);
        $first = $this->step(RuleSubType::POSTS_CONTENT, null, 3);

        $run = $this->runFor(PostFormat::SIMPLE_POST, null, [$second, $first]);

        $this->assertSame([3], $this->gate->generators($run)->pluck('id')->all());
    }

    public function test_a_step_naming_another_format_without_a_generic_one_leaves_nothing_to_run(): void
    {
        $carousel = $this->step(RuleSubType::POSTS_CONTENT, PostFormat::CAROUSEL, 1);

        $run = $this->runFor(PostFormat::SIMPLE_POST, null, [$carousel]);

        $this->assertTrue($this->gate->generators($run)->isEmpty());
    }

    public function test_select_is_the_single_implementation_of_the_priority_rule(): void
    {
        $generic = $this->step(RuleSubType::POSTS_IMAGE_FIX, null, 1);
        $carousel = $this->step(RuleSubType::POSTS_IMAGE_FIX, PostFormat::CAROUSEL, 2);
        $steps = new Collection([$generic, $carousel]);

        $this->assertSame($carousel, PostsStepGate::select($steps, RuleSubType::POSTS_IMAGE_FIX, PostFormat::CAROUSEL));
        $this->assertSame($generic, PostsStepGate::select($steps, RuleSubType::POSTS_IMAGE_FIX, PostFormat::SIMPLE_POST));
        $this->assertSame($generic, PostsStepGate::select($steps, RuleSubType::POSTS_IMAGE_FIX, null));
        $this->assertNull(PostsStepGate::select($steps, RuleSubType::POSTS_IMAGE_RETHINK, PostFormat::CAROUSEL));
    }

    public function test_a_step_of_another_rule_type_never_runs(): void
    {
        $ideas = $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::SIMPLE_POST, 1);

        $run = $this->runFor(PostFormat::SIMPLE_POST, null, [$ideas]);

        $this->assertFalse($this->gate->passes($run, $ideas));
    }
}
