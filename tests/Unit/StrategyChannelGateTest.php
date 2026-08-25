<?php

namespace Tests\Unit;

use App\Genie\Strategy\StrategyChannelGate;
use Tests\TestCase;

class StrategyChannelGateTest extends TestCase
{
    protected StrategyChannelGate $gate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gate = new StrategyChannelGate;
    }

    public function test_a_chosen_channel_is_selected(): void
    {
        $this->assertTrue(
            $this->gate->selects(['instagram' => true, 'tiktok' => false], 'instagram')
        );
    }

    public function test_a_rejected_channel_is_not_selected(): void
    {
        $this->assertFalse(
            $this->gate->selects(['instagram' => true, 'tiktok' => false], 'tiktok')
        );
    }

    public function test_a_channel_the_answer_never_mentions_is_not_selected(): void
    {
        // A sub-field added after the strategy was written has no key of its own.
        $this->assertFalse(
            $this->gate->selects(['instagram' => true], 'website')
        );
    }

    public function test_a_stringly_typed_answer_is_read_as_a_boolean(): void
    {
        $this->assertTrue($this->gate->selects(['instagram' => 'true'], 'instagram'));

        // Every non-empty string is truthy, so "false" is the case that matters.
        $this->assertFalse($this->gate->selects(['instagram' => 'false'], 'instagram'));
    }

    public function test_an_integer_answer_is_read_as_a_boolean(): void
    {
        $this->assertTrue($this->gate->selects(['instagram' => 1], 'instagram'));
        $this->assertFalse($this->gate->selects(['instagram' => 0], 'instagram'));
    }

    public function test_an_answer_that_is_not_a_map_selects_nothing(): void
    {
        $this->assertFalse($this->gate->selects(null, 'instagram'));
        $this->assertFalse($this->gate->selects('instagram', 'instagram'));
        $this->assertFalse($this->gate->selects([], 'instagram'));
    }
}
