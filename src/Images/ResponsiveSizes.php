<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class ResponsiveSizes
{
    public static function forRequest(ImageRequest $request): ?string
    {
        if ($request->sizes !== null) {
            return $request->sizes;
        }

        return $request->useAutoSizes ? 'auto' : null;
    }
}
