<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

final class FocalPointMetadata
{
    public const META_KEY_X = 'sproutset_focal_point_x';

    public const META_KEY_Y = 'sproutset_focal_point_y';

    public const DEFAULT_PERCENT = 50.0;

    public const MIN_PERCENT = 0.0;

    public const MAX_PERCENT = 100.0;

    public static function getDefaultPercentAsString(): string
    {
        return (string) self::DEFAULT_PERCENT;
    }

    public static function readCoordinatesFromMetadataArray(array $metadata): array
    {
        $x = isset($metadata[self::META_KEY_X]) ? (float) $metadata[self::META_KEY_X] : self::DEFAULT_PERCENT;
        $y = isset($metadata[self::META_KEY_Y]) ? (float) $metadata[self::META_KEY_Y] : self::DEFAULT_PERCENT;

        $x = max(self::MIN_PERCENT, min(self::MAX_PERCENT, $x));
        $y = max(self::MIN_PERCENT, min(self::MAX_PERCENT, $y));

        return [$x, $y];
    }
}
