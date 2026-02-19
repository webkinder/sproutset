<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

final readonly class NormalizedImageInput
{
    public function __construct(
        public int $attachmentId,
        public string $sizeName,
        public ?string $sizes,
        public ?string $alt,
        public ?int $width,
        public ?int $height,
        public ?string $class,
        public bool $useLazyLoading,
        public string $decodingMode,
        public bool $useAutoSizes,
        public bool $focalPoint,
        public ?float $focalPointX,
        public ?float $focalPointY,
    ) {}
}
