<?php

namespace Tests\Unit;

use App\Enums\PostFormat;
use App\Enums\RuleSubType;
use App\Genie\Runs\PostFormatGate;
use App\Models\AiRun;
use App\Models\Rule;
use App\Models\RuleStep;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PostFormatGateTest extends TestCase
{
    protected PostFormatGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = new PostFormatGate;
    }

    protected function runFor(?PostFormat $requested, array $steps = []): AiRun
    {
        $run = new AiRun(['parameters' => ['post_format' => $requested?->value]]);

        $rule = new Rule;
        $rule->setRelation('steps', new Collection($steps));
        $run->setRelation('rule', $rule);

        return $run;
    }

    protected function step(RuleSubType $subType, ?PostFormat $format = null, int $position = 1): RuleStep
    {
        $step = new RuleStep([
            'name' => $subType->name,
            'rule_sub_type' => $subType,
            'post_format' => $format,
            'position' => $position,
        ]);

        $step->id = $position;

        return $step;
    }

    public function test_any_format_matches_every_step_format(): void
    {
        $this->assertTrue($this->gate->matches(null, PostFormat::CAROUSEL));
        $this->assertTrue($this->gate->matches(null, PostFormat::LONG_VIDEO));
    }

    public function test_a_requested_format_matches_only_itself(): void
    {
        $this->assertTrue($this->gate->matches(PostFormat::CAROUSEL, PostFormat::CAROUSEL));
        $this->assertFalse($this->gate->matches(PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO));
    }

    public function test_a_step_without_a_format_never_matches(): void
    {
        $this->assertFalse($this->gate->matches(null, null));
        $this->assertFalse($this->gate->matches(PostFormat::CAROUSEL, null));
    }

    public function test_a_context_step_always_passes(): void
    {
        $run = $this->runFor(PostFormat::CAROUSEL);

        $this->assertTrue($this->gate->passes($run, $this->step(RuleSubType::IDEAS_INITIAL)));
    }

    public function test_a_generator_step_passes_when_its_format_was_requested(): void
    {
        $run = $this->runFor(PostFormat::CAROUSEL);

        $this->assertTrue($this->gate->passes($run, $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::CAROUSEL)));
        $this->assertFalse($this->gate->passes($run, $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::SIMPLE_POST)));
    }

    public function test_a_generator_step_passes_for_any_format(): void
    {
        $run = $this->runFor(null);

        $this->assertTrue($this->gate->passes($run, $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::SIMPLE_POST)));
    }

    public function test_a_generator_step_without_a_format_is_skipped(): void
    {
        $run = $this->runFor(null);

        $this->assertFalse($this->gate->passes($run, $this->step(RuleSubType::IDEAS_FORMAT)));
    }

    public function test_a_legacy_sub_type_is_skipped(): void
    {
        $run = $this->runFor(null);

        $this->assertFalse($this->gate->passes($run, $this->step(RuleSubType::IDEAS_MULTIPLE, PostFormat::CAROUSEL)));
    }

    public function test_generators_are_the_passing_format_steps_in_rule_order(): void
    {
        $run = $this->runFor(null, [
            $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::SHORT_VIDEO, 3),
            $this->step(RuleSubType::IDEAS_INITIAL, null, 1),
            $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::SIMPLE_POST, 2),
            $this->step(RuleSubType::IDEAS_FORMAT, null, 4),
        ]);

        $this->assertSame(
            ['SIMPLE_POST', 'SHORT_VIDEO'],
            $this->gate->generators($run)->map(fn (RuleStep $step) => $step->post_format->name)->all(),
        );
    }

    public function test_generators_narrow_to_the_requested_format(): void
    {
        $run = $this->runFor(PostFormat::SHORT_VIDEO, [
            $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::SIMPLE_POST, 2),
            $this->step(RuleSubType::IDEAS_FORMAT, PostFormat::SHORT_VIDEO, 3),
        ]);

        $this->assertCount(1, $this->gate->generators($run));
        $this->assertSame(PostFormat::SHORT_VIDEO, $this->gate->generators($run)->first()->post_format);
    }

    public function test_a_run_asking_for_an_unknown_format_reads_as_any(): void
    {
        $run = new AiRun(['parameters' => ['post_format' => 99]]);

        $this->assertNull(PostFormatGate::requested($run));
    }
}
