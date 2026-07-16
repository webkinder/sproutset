<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Attachments;

/**
 * A resolved WordPress media attachment.
 *
 * The irreducible data any responsive image needs, decoupled from the WordPress
 * functions that produce it. Size selection and srcset assembly live in the
 * component layer — this value object only carries the source's identity and
 * intrinsic dimensions.
 */
final readonly class Attachment
{
    public function __construct(
        public int $id,
        public string $url,
        public int $width,
        public int $height,
    ) {}
}
