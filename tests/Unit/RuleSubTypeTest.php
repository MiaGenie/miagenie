<?php

namespace Tests\Unit;

use App\Enums\RuleSubType;
use Tests\TestCase;

class RuleSubTypeTest extends TestCase
{
    public function test_the_initial_steps_are_context_steps(): void
    {
        $this->assertTrue(RuleSubType::IDEAS_INITIAL->isContext());
        $this->assertTrue(RuleSubType::DRAFTS_INITIAL->isContext());
        $this->assertTrue(RuleSubType::PRE_POSTS_INITIAL->isContext());

        $this->assertFalse(RuleSubType::IDEAS_FORMAT->isContext());
        $this->assertFalse(RuleSubType::BRIEFINGS->isContext());
        $this->assertFalse(RuleSubType::CHANNELS->isContext());
    }

    public function test_only_a_format_step_generates(): void
    {
        $this->assertTrue(RuleSubType::IDEAS_FORMAT->isGenerator());

        $this->assertFalse(RuleSubType::IDEAS_INITIAL->isGenerator());
        $this->assertFalse(RuleSubType::IDEAS_MULTIPLE->isGenerator());
        $this->assertFalse(RuleSubType::BRIEFINGS->isGenerator());
    }
}
