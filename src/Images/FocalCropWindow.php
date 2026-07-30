<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class FocalCropWindow
{
    /**
     * @return array{x: int, y: int, w: int, h: int}|null
     */
    public static function forTarget(
        int $originalWidth,
        int $originalHeight,
        int $targetWidth,
        int $targetHeight,
        FocalPoint $focal,
    ): ?array {
        if ($originalWidth <= 0 || $originalHeight <= 0 || $targetWidth <= 0 || $targetHeight <= 0) {
            return null;
        }

        $targetRatio = $targetWidth / $targetHeight;

        if ($originalWidth / $originalHeight > $targetRatio) {
            $height = $originalHeight;
            $width = (int) round($height * $targetRatio);
        } else {
            $width = $originalWidth;
            $height = (int) round($width / $targetRatio);
        }

        $centerX = $originalWidth * $focal->x / 100;
        $centerY = $originalHeight * $focal->y / 100;

        $x = (int) round($centerX - $width / 2);
        $y = (int) round($centerY - $height / 2);

        $x = max(0, min($x, $originalWidth - $width));
        $y = max(0, min($y, $originalHeight - $height));

        return ['x' => $x, 'y' => $y, 'w' => $width, 'h' => $height];
    }
}
