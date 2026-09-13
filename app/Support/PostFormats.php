<?php

namespace App\Support;

use App\Enums\PostFormat;

/**
 * Which post formats each social provider accepts.
 *
 * The keys are the provider strings from `Inovector\Mixpost\SocialProviderManager::providers()`
 */
final class PostFormats
{
    /**
     * @var array<string, list<PostFormat>>
     */
    private const MAP = [
        'twitter' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO],
        'facebook_page' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO, PostFormat::LONG_VIDEO],
        'instagram' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO],
        'threads' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO],
        'mastodon' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO],
        'youtube' => [PostFormat::SHORT_VIDEO, PostFormat::LONG_VIDEO],
        'gbp' => [PostFormat::SIMPLE_POST],
        'pinterest' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO],
        'linkedin' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO, PostFormat::LONG_VIDEO],
        'linkedin_page' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO, PostFormat::LONG_VIDEO],
        'tiktok' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO],
        'bluesky' => [PostFormat::SIMPLE_POST, PostFormat::CAROUSEL, PostFormat::SHORT_VIDEO],
    ];

    /**
     * The formats a provider accepts, or an empty list when the key is unrecognised.
     *
     * @return list<PostFormat>
     */
    public static function for(string $provider): array
    {
        return self::MAP[$provider] ?? [];
    }

    /**
     * The same list as snake_case titles, ready to drop into a prompt.
     *
     * @return list<string>
     */
    public static function titlesFor(string $provider): array
    {
        return array_map(
            static fn (PostFormat $format): string => $format->title(),
            self::for($provider)
        );
    }

    public static function supports(string $provider, PostFormat $format): bool
    {
        return in_array($format, self::for($provider), true);
    }

    /**
     * Every provider that accepts the given format.
     *
     * @return list<string>
     */
    public static function providersFor(PostFormat $format): array
    {
        return array_values(array_filter(
            array_keys(self::MAP),
            static fn (string $provider): bool => self::supports($provider, $format)
        ));
    }

    /**
     * Whether the key is a provider we have a row for, as opposed to one we simply reject.
     */
    public static function isKnown(string $provider): bool
    {
        return array_key_exists($provider, self::MAP);
    }

    /**
     * @return array<string, list<PostFormat>>
     */
    public static function all(): array
    {
        return self::MAP;
    }
}
