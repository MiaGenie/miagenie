<?php

namespace Tests\Unit;

use App\Enums\Modality;
use Tests\TestCase;

class ModalityTest extends TestCase
{
    public function test_only_text_and_image_can_be_configured(): void
    {
        $this->assertSame([Modality::TEXT->value, Modality::IMAGE->value], Modality::configurable()->pluck('value')->all());
        $this->assertSame(['text', 'image'], Modality::configurable()->pluck('title')->all());
    }

    public function test_files_are_the_result_of_image_and_video_only(): void
    {
        $this->assertFalse(Modality::TEXT->isFile());
        $this->assertTrue(Modality::IMAGE->isFile());
        $this->assertTrue(Modality::VIDEO->isFile());
    }
}
