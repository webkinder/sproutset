<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

/**
 * Pure computation of the CSS focal-point style.
 *
 * Focal coordinates are percentages (0–100) emitted into `object-position`,
 * paired with `object-fit: cover` so the position actually crops the image.
 * Nothing is emitted when the focal point is disabled or either coordinate is
 * missing. Holds no WordPress dependency, so it is exercised in the fast
 * Testbench lane.
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
            'object-fit: cover; object-position: %s%% %s%%;',
            self::percent($request->focalPointX),
            self::percent($request->focalPointY),
        );
    }

    private static function percent(float $value): string
    {
        return (string) round($value, 4);
    }
}
