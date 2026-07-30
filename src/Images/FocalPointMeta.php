<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class FocalPointMeta
{
    public const string META_KEY_X = '_sproutset_focal_point_x';

    public const string META_KEY_Y = '_sproutset_focal_point_y';

    public const string META_KEY_APPLIED = '_sproutset_focal_applied';

    public const float DEFAULT_PERCENT = 50.0;

    public const float MIN_PERCENT = 0.0;

    public const float MAX_PERCENT = 100.0;

    public static function read(int $attachmentId): ?FocalPoint
    {
        $x = get_post_meta($attachmentId, self::META_KEY_X, true);
        $y = get_post_meta($attachmentId, self::META_KEY_Y, true);

        if (! is_numeric($x) || ! is_numeric($y)) {
            return null;
        }

        $focal = new FocalPoint(self::clamp((float) $x), self::clamp((float) $y));

        return $focal->isCenter() ? null : $focal;
    }

    public static function write(int $attachmentId, float $x, float $y): void
    {
        update_post_meta($attachmentId, self::META_KEY_X, self::clamp($x));
        update_post_meta($attachmentId, self::META_KEY_Y, self::clamp($y));
    }

    public static function clamp(float $value): float
    {
        return max(self::MIN_PERCENT, min(self::MAX_PERCENT, $value));
    }

    /**
     * @return array<string, string>
     */
    public static function appliedAt(int $attachmentId): array
    {
        $applied = get_post_meta($attachmentId, self::META_KEY_APPLIED, true);

        if (! is_array($applied)) {
            return [];
        }

        $result = [];

        foreach ($applied as $size => $signature) {
            if (is_string($size) && is_string($signature)) {
                $result[$size] = $signature;
            }
        }

        return $result;
    }

    public static function markApplied(int $attachmentId, string $sizeName, string $signature): void
    {
        $applied = self::appliedAt($attachmentId);
        $applied[$sizeName] = $signature;

        update_post_meta($attachmentId, self::META_KEY_APPLIED, $applied);
    }

    public static function clearApplied(int $attachmentId): void
    {
        delete_post_meta($attachmentId, self::META_KEY_APPLIED);
    }
}
