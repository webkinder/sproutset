<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

/**
 * Pure computation of the `<img>` `sizes` attribute from request intent.
 *
 * An explicit `sizes` override always wins; otherwise `auto` is emitted when
 * automatic sizing is enabled, and nothing when neither applies. Holds no
 * WordPress dependency, so it is exercised in the fast Testbench lane.
 */
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
