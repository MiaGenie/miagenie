<?php

namespace App\Genie\Posts;

use App\Enums\PostFormat;
use App\Genie\Runs\PostFormatGate;
use App\Models\AiRun;

/**
 * What a posts run was asked for, read from and written to the run's `parameters`.
 *
 * A run always works on one idea and the Mixpost post produced from it. The format is the idea's,
 * stored under the gate's key so only that format's steps run. `only` narrows a regeneration to
 * the text or the image steps; `feedback` is what the reviewer wants changed; `images` false
 * creates the post without drawing (the caption only), to be drawn later from the post.
 */
final class PostsRequest
{
    public const ONLY_TEXT = 'text';

    public const ONLY_IMAGES = 'images';

    public function __construct(
        public readonly int $idea,
        public readonly int $post,
        public readonly ?PostFormat $postFormat,
        public readonly ?string $only = null,
        public readonly ?string $feedback = null,
        public readonly bool $images = true,
    ) {}

    /**
     * Tolerant of a missing or malformed key, like IdeasRequest.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function fromArray(array $parameters): self
    {
        $format = $parameters[PostFormatGate::PARAMETER] ?? null;
        $only = $parameters['only'] ?? null;
        $feedback = $parameters['feedback'] ?? null;

        return new self(
            (int) ($parameters['idea'] ?? 0),
            (int) ($parameters['post'] ?? 0),
            is_numeric($format) ? PostFormat::tryFrom((int) $format) : null,
            in_array($only, [self::ONLY_TEXT, self::ONLY_IMAGES], true) ? $only : null,
            is_string($feedback) && filled($feedback) ? $feedback : null,
            filter_var($parameters['images'] ?? true, FILTER_VALIDATE_BOOLEAN),
        );
    }

    public static function fromRun(AiRun $run): self
    {
        return self::fromArray($run->parameters ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'idea' => $this->idea,
            'post' => $this->post,
            PostFormatGate::PARAMETER => $this->postFormat?->value,
            'only' => $this->only,
            'feedback' => $this->feedback,
            'images' => $this->images,
        ];
    }

    public function wantsText(): bool
    {
        return $this->only !== self::ONLY_IMAGES;
    }

    /**
     * Images are drawn unless the run is text-only or the post was created without them (the
     * `images` flag from the create modal); a later "generate images" run sets both back.
     */
    public function wantsImages(): bool
    {
        return $this->only !== self::ONLY_TEXT && $this->images;
    }

    public function isRegeneration(): bool
    {
        return $this->only !== null || $this->feedback !== null;
    }

    public function hasFeedback(): bool
    {
        return $this->feedback !== null;
    }
}
