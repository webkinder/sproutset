<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final readonly class FocalPointConfig
{
    public function __construct(
        public bool $enabled,
    ) {}
}
