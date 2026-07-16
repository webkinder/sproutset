<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

/**
 * Boot-safe default resolver: resolves nothing until the WordPress-backed
 * implementation lands. Keeps the front end from fatally requiring a resolver.
 */
final class NullImageResolver implements ImageResolver
{
    public function resolve(ImageRequest $request): ?ResolvedImage
    {
        return null;
    }
}
