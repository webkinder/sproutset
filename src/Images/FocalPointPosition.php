<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class FocalPointPosition
{
    public static function forCover(?FocalPoint $focal, bool $coverInPlay): ?string
    {
        if (! $coverInPlay) {
            return null;
        }

        if (! $focal instanceof FocalPoint || $focal->isCenter()) {
            return 'object-fit: cover;';
        }

        return sprintf(
            'object-fit: cover; object-position: %s%% %s%%;',
            self::percent($focal->x),
            self::percent($focal->y),
        );
    }

    private static function percent(float $value): string
    {
        return (string) round($value, 4);
    }
}
