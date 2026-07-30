<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class NullImageResolver implements ImageResolver
{
    public function resolve(ImageRequest $request): ?ResolvedImage
    {
        return null;
    }
}
