<?php

namespace Tests\Unit;

use App\Genie\Posts\MixpostCaption;
use Tests\TestCase;

class MixpostCaptionTest extends TestCase
{
    public function test_each_line_becomes_a_div(): void
    {
        $this->assertSame('<div>One</div><div>Two</div><div></div><div>Four</div>', MixpostCaption::toHtml("One\nTwo\n\nFour"));
        $this->assertSame('<div>One</div><div>Two</div>', MixpostCaption::toHtml("One\r\nTwo"));
        $this->assertSame('', MixpostCaption::toHtml(null));
        $this->assertSame('', MixpostCaption::toHtml(''));
    }

    public function test_the_text_is_read_back_from_divs_paragraphs_and_breaks(): void
    {
        $this->assertSame("One\nTwo\n\nFour", MixpostCaption::toText('<div>One</div><div>Two</div><div></div><div>Four</div>'));
        $this->assertSame("One\nTwo", MixpostCaption::toText('<p>One</p><p>Two</p>'));
        $this->assertSame("One\nTwo", MixpostCaption::toText('One<br>Two'));
        $this->assertSame('Fish & chips', MixpostCaption::toText('<div>Fish &amp; chips</div>'));
        $this->assertSame('', MixpostCaption::toText(null));
        $this->assertSame('', MixpostCaption::toText('   '));
    }

    public function test_the_round_trip_is_stable(): void
    {
        $text = "Launch day!\n\nThree things to know:\n1. It's live";

        $this->assertSame($text, MixpostCaption::toText(MixpostCaption::toHtml($text)));
    }
}
