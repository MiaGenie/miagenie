<?php

namespace App\Genie\Images;

use InvalidArgumentException;
use Laravel\Ai\PendingResponses\PendingImageGeneration;

/**
 * The shape and quality asked of the image provider.
 *
 * By default no size is sent, so the provider falls back to its own default (OpenAI's `auto`, the
 * model's choice on Gemini / OpenRouter / xAI) and the aspect ratio the step's prompt asks for is
 * what shapes the image; a revision with a reference image keeps that image's shape. A size, when
 * given, is one of the SDK's aspect ratios, which each provider maps to its own pixel dimensions,
 * and then overrides whatever the prompt says.
 */
final readonly class ImageOptions
{
    public const SIZES = ['1:1', '2:3', '3:2'];

    public const QUALITIES = ['low', 'medium', 'high'];

    public function __construct(
        public ?string $size = null,
        public string $quality = 'medium',
    ) {
        if ($size !== null && ! in_array($size, self::SIZES, true)) {
            throw new InvalidArgumentException("Unsupported image size [{$size}].");
        }

        if (! in_array($quality, self::QUALITIES, true)) {
            throw new InvalidArgumentException("Unsupported image quality [{$quality}].");
        }
    }

    public static function default(): self
    {
        return new self;
    }

    public function apply(PendingImageGeneration $generation): PendingImageGeneration
    {
        if ($this->size !== null) {
            $generation->size($this->size);
        }

        return $generation->quality($this->quality);
    }
}
