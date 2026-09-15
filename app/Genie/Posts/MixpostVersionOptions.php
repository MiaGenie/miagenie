<?php

namespace App\Genie\Posts;

/**
 * The per-provider options a new post version starts with.
 *
 * Mirrors `versionOptions()` in resources/js/Composables/usePostVersions.js, which is what the
 * editor writes for a version it creates itself, so a version Genie creates reads the same way.
 * The title fills the two providers that publish one.
 */
final class MixpostVersionOptions
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(?string $title = null, ?string $link = null): array
    {
        return [
            'mastodon' => ['sensitive' => false],
            'facebook_page' => ['type' => 'post'],
            'instagram' => ['type' => 'post'],
            'youtube' => ['title' => $title, 'status' => 'public'],
            'gbp' => [
                'type' => 'post',
                'button' => 'NONE',
                'button_link' => '',
                'offer_has_details' => false,
                'coupon_code' => '',
                'offer_link' => '',
                'terms' => '',
                'event_title' => '',
                'start_date' => null,
                'end_date' => null,
                'event_has_time' => false,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ],
            'pinterest' => ['title' => $title, 'link' => $link, 'boards' => ['account-0' => null]],
            'linkedin' => ['visibility' => 'PUBLIC'],
            'tiktok' => [
                'privacy_level' => ['account-0' => ''],
                'allow_comments' => ['account-0' => false],
                'allow_duet' => ['account-0' => false],
                'allow_stitch' => ['account-0' => false],
                'content_disclosure' => ['account-0' => false],
                'brand_organic_toggle' => ['account-0' => false],
                'brand_content_toggle' => ['account-0' => false],
            ],
        ];
    }

    /**
     * Set the title on the providers that publish one, leaving every other option as stored.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function withTitle(array $options, ?string $title): array
    {
        $options['youtube'] = array_merge($options['youtube'] ?? ['status' => 'public'], ['title' => $title]);
        $options['pinterest'] = array_merge($options['pinterest'] ?? ['link' => null, 'boards' => ['account-0' => null]], ['title' => $title]);

        return $options;
    }

    /**
     * The title a version carries, from either provider that publishes one.
     *
     * @param  array<string, mixed>|null  $options
     */
    public static function titleOf(?array $options): ?string
    {
        $title = $options['pinterest']['title'] ?? $options['youtube']['title'] ?? null;

        return is_string($title) && filled($title) ? $title : null;
    }
}
