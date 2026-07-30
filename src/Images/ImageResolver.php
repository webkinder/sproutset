<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

interface ImageResolver
{
    public function resolve(ImageRequest $request): ?ResolvedImage;
}
