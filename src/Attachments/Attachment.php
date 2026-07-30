<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Attachments;

final readonly class Attachment
{
    public function __construct(
        public int $id,
        public string $url,
        public int $width,
        public int $height,
    ) {}
}
