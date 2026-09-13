<?php

namespace Tests\Unit;

use App\Enums\FunnelStage;
use Tests\TestCase;

class FunnelStageTest extends TestCase
{
    public function test_global_is_any_stage(): void
    {
        $this->assertTrue(FunnelStage::GLOBAL->isAny());
        $this->assertFalse(FunnelStage::AWARENESS->isAny());
    }

    public function test_the_concrete_stages_leave_global_out(): void
    {
        $this->assertNotContains(FunnelStage::GLOBAL, FunnelStage::concrete());
        $this->assertCount(count(FunnelStage::cases()) - 1, FunnelStage::concrete());
    }

    public function test_a_stage_is_found_by_its_title(): void
    {
        $this->assertSame(FunnelStage::CONVERSION, FunnelStage::fromTitle('conversion'));
        $this->assertSame(FunnelStage::PROBLEM_AWARENESS, FunnelStage::fromTitle('problem_awareness'));
    }

    public function test_an_unknown_title_is_null(): void
    {
        $this->assertNull(FunnelStage::fromTitle('decision'));
        $this->assertNull(FunnelStage::fromTitle(''));
    }
}
