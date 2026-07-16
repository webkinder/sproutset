<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

/**
 * The resolver's output: everything the component needs to render an `<img>`,
 * with no knowledge of how the values were derived. A `null` result from an
 * {@see ImageResolver} means "render nothing".
 */
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
