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

        if ($request->useAutoSizes && $request->loading !== 'eager') {
            return 'auto';
        }

        return null;
    }
}
