<?php

namespace Tests\Unit;

use App\Enums\GeniePostStatus;
use Tests\TestCase;

class GeniePostStatusTest extends TestCase
{
    public function test_titles_match_the_generic_lang_keys(): void
    {
        $this->assertSame(['generating', 'ready', 'failed'], GeniePostStatus::withTitle()->pluck('title')->all());
    }

    public function test_state_helpers(): void
    {
        $this->assertTrue(GeniePostStatus::FAILED->isError());
        $this->assertTrue(GeniePostStatus::GENERATING->requiresUpdate());
        $this->assertTrue(GeniePostStatus::READY->isComplete());
        $this->assertFalse(GeniePostStatus::READY->isError());
        $this->assertFalse(GeniePostStatus::READY->requiresUpdate());
    }
}
