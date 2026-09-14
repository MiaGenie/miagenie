<?php

namespace App\Genie\Images;

use InvalidArgumentException;
use Laravel\Ai\PendingResponses\PendingImageGeneration;

/**
 * The shape and quality asked of the image provider.
 *
 * Sizes are the SDK's aspect ratios, which each provider maps to its own pixel dimensions. Square
 * at medium quality is the default every provider accepts; a run type that knows the post format
 * picks otherwise through Runner::imageOptions().
 */
final readonly class ImageOptions
{
    public const SIZES = ['1:1', '2:3', '3:2'];

    public const QUALITIES = ['low', 'medium', 'high'];

    public function __construct(
        public string $size = '1:1',
        public string $quality = 'medium',
    ) {
        if (! in_array($size, self::SIZES, true)) {
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
        return $generation->size($this->size)->quality($this->quality);
    }
}
