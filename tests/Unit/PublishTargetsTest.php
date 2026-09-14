<?php

namespace Tests\Unit;

use App\Enums\PostFormat;
use App\Support\PostFormats;
use App\Support\PublishTargets;
use Tests\TestCase;

class PublishTargetsTest extends TestCase
{
    public function test_available_is_the_configured_providers_that_accept_the_format_in_map_order(): void
    {
        $targets = new PublishTargets(['youtube', 'instagram', 'gbp']);

        $this->assertSame(['instagram', 'gbp'], $targets->available(PostFormat::SIMPLE_POST));
        $this->assertSame(['instagram'], $targets->available(PostFormat::CAROUSEL));
        $this->assertSame(['youtube'], $targets->available(PostFormat::LONG_VIDEO));
    }

    public function test_no_format_offers_every_configured_provider(): void
    {
        $targets = new PublishTargets(['youtube', 'instagram']);

        $this->assertSame(['instagram', 'youtube'], $targets->available(null));
        $this->assertSame(['instagram', 'youtube'], $targets->configured());
    }

    public function test_an_unknown_or_repeated_configured_provider_is_dropped(): void
    {
        $targets = new PublishTargets(['myspace', 'instagram', 'instagram']);

        $this->assertSame(['instagram'], $targets->configured());
        $this->assertSame(['instagram'], $targets->available(null));
    }

    public function test_keep_filters_to_what_is_on_offer_without_repeats(): void
    {
        $targets = new PublishTargets(['youtube', 'instagram', 'linkedin']);

        $kept = $targets->keep(['linkedin', 'instagram', 'instagram', 'youtube', 'myspace', 7], PostFormat::CAROUSEL);

        $this->assertSame(['instagram', 'linkedin'], $kept);
    }

    public function test_by_format_lists_every_format(): void
    {
        $targets = new PublishTargets(['youtube', 'gbp']);

        $byFormat = $targets->byFormat();

        $this->assertSame(array_map(fn (PostFormat $format) => $format->value, PostFormat::cases()), array_keys($byFormat));
        $this->assertSame(['gbp'], $byFormat[PostFormat::SIMPLE_POST->value]);
        $this->assertSame([], $byFormat[PostFormat::CAROUSEL->value]);
        $this->assertSame(['youtube'], $byFormat[PostFormat::SHORT_VIDEO->value]);
    }

    public function test_nothing_configured_offers_nothing(): void
    {
        $targets = new PublishTargets([]);

        $this->assertSame([], $targets->configured());
        $this->assertSame([], $targets->available(null));
        $this->assertSame([], $targets->available(PostFormat::SIMPLE_POST));
        $this->assertSame([], $targets->keep(['instagram'], null));
    }

    public function test_every_provider_configured_offers_the_same_as_the_format_map(): void
    {
        $targets = new PublishTargets(array_keys(PostFormats::all()));

        foreach (PostFormat::cases() as $format) {
            $this->assertSame(PostFormats::providersFor($format), $targets->available($format));
        }
    }
}
