<?php

namespace Tests\Unit;

use App\Enums\PostFormat;
use Tests\TestCase;

class PostFormatTest extends TestCase
{
    public function test_a_simple_post_carries_exactly_one_image(): void
    {
        $this->assertSame([1, 1], PostFormat::SIMPLE_POST->imageRange());
    }

    public function test_a_carousel_carries_three_to_ten_images(): void
    {
        $this->assertSame([3, 10], PostFormat::CAROUSEL->imageRange());
    }

    public function test_a_video_carries_no_images(): void
    {
        $this->assertNull(PostFormat::SHORT_VIDEO->imageRange());
        $this->assertNull(PostFormat::LONG_VIDEO->imageRange());
    }

    public function test_the_ranges_are_keyed_by_value_and_leave_videos_out(): void
    {
        $this->assertSame([
            PostFormat::SIMPLE_POST->value => [1, 1],
            PostFormat::CAROUSEL->value => [3, 10],
        ], PostFormat::imageRanges());
    }
}
