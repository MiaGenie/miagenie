<?php

namespace App\Support;

use App\Enums\PostFormat;
use Inovector\Mixpost\Models\Account;

/**
 * The social providers an idea can be published to.
 *
 * A provider is on offer when it accepts the idea's format (`PostFormats`) and the workspace has an
 * account for it. Built once over the workspace's configured provider keys so the rest is pure.
 * Shared by the ideas and posts run types and by their workspace pages.
 */
final class PublishTargets
{
    /**
     * @var list<string>
     */
    private array $configured;

    /**
     * @param  list<string>  $configured  the provider keys of the workspace's accounts
     */
    public function __construct(array $configured)
    {
        $known = array_keys(PostFormats::all());

        $this->configured = array_values(array_filter(
            $known,
            static fn (string $provider): bool => in_array($provider, $configured, true)
        ));
    }

    public static function forWorkspace(int $workspaceId): self
    {
        $providers = Account::query()
            ->byWorkspace($workspaceId)
            ->pluck('provider')
            ->unique()
            ->values()
            ->all();

        return new self($providers);
    }

    /**
     * The configured providers we have a format row for, in `PostFormats` order.
     *
     * @return list<string>
     */
    public function configured(): array
    {
        return $this->configured;
    }

    /**
     * The configured providers that accept a format; every configured provider when there is none.
     *
     * @return list<string>
     */
    public function available(?PostFormat $format): array
    {
        $accepting = $format ? PostFormats::providersFor($format) : array_keys(PostFormats::all());

        return array_values(array_filter(
            $this->configured,
            static fn (string $provider): bool => in_array($provider, $accepting, true)
        ));
    }

    /**
     * The requested providers that are on offer for the format, without repeats, in offer order.
     *
     * @param  array<mixed>  $requested
     * @return list<string>
     */
    public function keep(array $requested, ?PostFormat $format): array
    {
        return array_values(array_filter(
            $this->available($format),
            static fn (string $provider): bool => in_array($provider, $requested, true)
        ));
    }

    /**
     * The providers on offer per format, keyed by the enum value the frontend holds.
     *
     * @return array<int, list<string>>
     */
    public function byFormat(): array
    {
        $providers = [];

        foreach (PostFormat::cases() as $format) {
            $providers[$format->value] = $this->available($format);
        }

        return $providers;
    }
}
