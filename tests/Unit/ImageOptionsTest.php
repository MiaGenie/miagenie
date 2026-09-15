<?php

namespace Tests\Unit;

use App\Genie\Images\ImageOptions;
use InvalidArgumentException;
use Laravel\Ai\PendingResponses\PendingImageGeneration;
use Tests\TestCase;

class ImageOptionsTest extends TestCase
{
    public function test_the_default_is_a_square_medium_image(): void
    {
        $options = ImageOptions::default();

        $this->assertSame('1:1', $options->size);
        $this->assertSame('medium', $options->quality);
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
