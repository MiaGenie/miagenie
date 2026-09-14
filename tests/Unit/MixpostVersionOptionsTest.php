<?php

namespace Tests\Unit;

use App\Genie\Posts\MixpostVersionOptions;
use Tests\TestCase;

class MixpostVersionOptionsTest extends TestCase
{
    public function test_every_provider_the_editor_writes_options_for_is_present(): void
    {
        $options = MixpostVersionOptions::defaults();

        $this->assertSame(
            ['mastodon', 'facebook_page', 'instagram', 'youtube', 'gbp', 'pinterest', 'linkedin', 'tiktok'],
            array_keys($options),
        );
        $this->assertSame('', $options['tiktok']['privacy_level']['account-0']);
        $this->assertNull($options['pinterest']['boards']['account-0']);
    }

    public function test_the_title_reaches_pinterest_and_youtube(): void
    {
        $options = MixpostVersionOptions::defaults('Launch day', 'https://example.test');

        $this->assertSame('Launch day', $options['pinterest']['title']);
        $this->assertSame('Launch day', $options['youtube']['title']);
        $this->assertSame('https://example.test', $options['pinterest']['link']);
        $this->assertSame('Launch day', MixpostVersionOptions::titleOf($options));
    }

    public function test_with_title_changes_only_the_two_titles(): void
    {
        $stored = ['instagram' => ['type' => 'reel'], 'pinterest' => ['title' => 'Old', 'link' => 'x', 'boards' => ['account-0' => 5]]];

        $options = MixpostVersionOptions::withTitle($stored, 'New');

        $this->assertSame('reel', $options['instagram']['type']);
        $this->assertSame('New', $options['pinterest']['title']);
        $this->assertSame('x', $options['pinterest']['link']);
        $this->assertSame(5, $options['pinterest']['boards']['account-0']);
        $this->assertSame('New', $options['youtube']['title']);
        $this->assertSame('public', $options['youtube']['status']);
    }

    public function test_no_title_reads_as_null(): void
    {
        $this->assertNull(MixpostVersionOptions::titleOf(null));
        $this->assertNull(MixpostVersionOptions::titleOf(['pinterest' => ['title' => '']]));
    }
}
