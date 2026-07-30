<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final readonly class FocalPoint
{
    public function __construct(
        public float $x,
        public float $y,
    ) {}

    public function isCenter(): bool
    {
        return $this->x === 50.0 && $this->y === 50.0;
    }
}
