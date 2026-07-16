<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

/**
 * Normalized, typed inputs for a single image render.
 *
 * Produced by {@see ImageInputNormalizer} from the component's loose Blade
 * attributes. Presentation-only fields (`class`, `decoding`) are read by the
 * component; the rest describe what an {@see ImageResolver} must resolve.
 */
final readonly class ImageRequest
{
    public function __construct(
        public int $attachmentId,
        public string $sizeName,
        public ?string $sizes,
        public ?string $alt,
        public ?int $width,
        public ?int $height,
        public ?string $class,
        public string $loading,
        public string $decoding,
        public bool $useAutoSizes,
        public bool $focalPoint,
        public ?float $focalPointX,
        public ?float $focalPointY,
    ) {}
}
