<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final readonly class ResolvedImage
{
    public function __construct(
        public ?string $src,
        public ?string $srcset,
        public ?string $sizes,
        public ?int $width,
        public ?int $height,
        public string $alt,
        public ?string $style,
        public bool $isSvg,
    ) {}
}
