<?php

namespace Tests\Unit;

use App\Genie\Images\ImageOptions;
use InvalidArgumentException;
use Laravel\Ai\PendingResponses\PendingImageGeneration;
use Tests\TestCase;

class ImageOptionsTest extends TestCase
{
    public function test_the_default_leaves_the_size_to_the_prompt_at_medium_quality(): void
    {
        $options = ImageOptions::default();

        $this->assertNull($options->size);
        $this->assertSame('medium', $options->quality);
    }

    public function test_apply_without_a_size_sends_none_so_the_prompt_decides_the_ratio(): void
    {
        $generation = ImageOptions::default()->apply(new PendingImageGeneration('A tall 9:16 poster of a chair'));

        $this->assertNull($generation->size);
        $this->assertSame('medium', $generation->quality);
    }

    public function test_an_unknown_size_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageOptions('16:9');
    }

    public function test_an_unknown_quality_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageOptions('1:1', 'ultra');
    }

    public function test_apply_sets_size_and_quality_on_the_generation(): void
    {
        $generation = (new ImageOptions('2:3', 'high'))->apply(new PendingImageGeneration('A chair'));

        $this->assertSame('2:3', $generation->size);
        $this->assertSame('high', $generation->quality);
    }
}
