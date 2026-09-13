<?php

namespace Tests\Unit;

use App\Enums\PostFormat;
use App\Support\PostFormats;
use Inovector\Mixpost\Facades\SocialProviderManager;
use Tests\TestCase;

class PostFormatsTest extends TestCase
{
    public function test_every_mapped_provider_exists_in_mixpost(): void
    {
        $mixpost = array_keys(SocialProviderManager::providers());

        foreach (array_keys(PostFormats::all()) as $provider) {
            $this->assertContains($provider, $mixpost, "Mapped provider [$provider] is not a Mixpost provider.");
        }
    }

    public function test_every_mixpost_provider_has_formats(): void
    {
        // A provider added by a package update must fail here rather than silently return [].
        foreach (array_keys(SocialProviderManager::providers()) as $provider) {
            $this->assertTrue(PostFormats::isKnown($provider), "Mixpost provider [$provider] has no format row.");
            $this->assertNotEmpty(PostFormats::for($provider), "Mixpost provider [$provider] has an empty format row.");
        }
    }

    public function test_unknown_provider_has_no_formats(): void
    {
        $this->assertSame([], PostFormats::for('myspace'));
        $this->assertSame([], PostFormats::titlesFor('myspace'));
        $this->assertFalse(PostFormats::supports('myspace', PostFormat::SIMPLE_POST));
    }

    public function test_is_known_rejects_unknown_provider(): void
    {
        $this->assertFalse(PostFormats::isKnown('myspace'));
        $this->assertTrue(PostFormats::isKnown('instagram'));
    }

    public function test_supports_answers_for_a_known_pairing(): void
    {
        $this->assertTrue(PostFormats::supports('youtube', PostFormat::LONG_VIDEO));
        $this->assertFalse(PostFormats::supports('youtube', PostFormat::CAROUSEL));
        $this->assertFalse(PostFormats::supports('youtube', PostFormat::SIMPLE_POST));

        $this->assertTrue(PostFormats::supports('gbp', PostFormat::SIMPLE_POST));
        $this->assertFalse(PostFormats::supports('gbp', PostFormat::SHORT_VIDEO));
    }

    public function test_titles_for_returns_snake_case_format_keys(): void
    {
        $this->assertSame(['short_video', 'long_video'], PostFormats::titlesFor('youtube'));
    }

    public function test_providers_for_returns_every_provider_supporting_the_format(): void
    {
        $expected = [];

        foreach (PostFormats::all() as $provider => $formats) {
            if (in_array(PostFormat::LONG_VIDEO, $formats, true)) {
                $expected[] = $provider;
            }
        }

        $this->assertSame($expected, PostFormats::providersFor(PostFormat::LONG_VIDEO));
        $this->assertContains('youtube', PostFormats::providersFor(PostFormat::LONG_VIDEO));
        $this->assertNotContains('instagram', PostFormats::providersFor(PostFormat::LONG_VIDEO));
    }

    public function test_no_provider_repeats_a_format(): void
    {
        foreach (PostFormats::all() as $provider => $formats) {
            $this->assertSame(
                count($formats),
                count(array_unique(array_column($formats, 'value'))),
                "Provider [$provider] lists a format twice."
            );
        }
    }
}
