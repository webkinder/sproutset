<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final readonly class AvifConfig
{
    public function __construct(
        public bool $enabled,
        public int $quality,
    ) {}
}
