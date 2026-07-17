<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

/**
 * Pure computation of the CSS `object-position` style from a focal point.
 *
 * Fractional focal coordinates (0–1) map to percentages. Nothing is emitted
 * when the focal point is disabled or either coordinate is missing. Holds no
 * WordPress dependency, so it is exercised in the fast Testbench lane.
 */
final class FocalPointPosition
{
    public static function forRequest(ImageRequest $request): ?string
    {
        if (! $request->focalPoint) {
            return null;
        }

        if ($request->focalPointX === null || $request->focalPointY === null) {
            return null;
        }

        return sprintf(
            'object-position: %s%% %s%%;',
            self::percent($request->focalPointX),
            self::percent($request->focalPointY),
        );
    }

    private static function percent(float $fraction): string
    {
        return (string) round($fraction * 100, 4);
    }
}
