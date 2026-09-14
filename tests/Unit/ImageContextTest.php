<?php

namespace Tests\Unit;

use App\Genie\Images\ImageContext;
use Tests\TestCase;

class ImageContextTest extends TestCase
{
    public function test_values_answer_the_three_bare_placeholders_as_strings(): void
    {
        $context = new ImageContext('A red chair', 'Make it blue', 3);

        $this->assertSame([
            'brief' => 'A red chair',
            'feedback' => 'Make it blue',
            'position' => '3',
        ], $context->values());
    }

    public function test_missing_brief_and_feedback_render_as_empty_strings(): void
    {
        $context = new ImageContext;

        $this->assertSame(['brief' => '', 'feedback' => '', 'position' => '1'], $context->values());
        $this->assertFalse($context->hasFeedback());
    }

    public function test_feedback_counts_only_when_it_says_something(): void
    {
        $this->assertTrue((new ImageContext(null, 'Less text'))->hasFeedback());
        $this->assertFalse((new ImageContext(null, '   '))->hasFeedback());
        $this->assertFalse((new ImageContext(null, ''))->hasFeedback());
    }
}
