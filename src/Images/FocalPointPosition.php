<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

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
