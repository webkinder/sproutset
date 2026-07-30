<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class PresentedDimensions
{
    /**
     * @return array{width: int, height: int, cover: bool}|null
     */
    public static function forSource(
        int $targetWidth,
        int $targetHeight,
        bool $crop,
        int $sourceWidth,
        int $sourceHeight,
    ): ?array {
        if ($sourceWidth <= 0 || $sourceHeight <= 0 || $targetWidth <= 0) {
            return null;
        }

        if ($crop && $targetHeight > 0) {
            return [
                'width' => $targetWidth,
                'height' => $targetHeight,
                'cover' => $sourceWidth < $targetWidth || $sourceHeight < $targetHeight,
            ];
        }

        if ($targetHeight <= 0) {
            return [
                'width' => $targetWidth,
                'height' => (int) round($targetWidth * $sourceHeight / $sourceWidth),
                'cover' => false,
            ];
        }

        $heightFromWidth = (int) round($targetWidth * $sourceHeight / $sourceWidth);

        if ($heightFromWidth <= $targetHeight) {
            return ['width' => $targetWidth, 'height' => $heightFromWidth, 'cover' => false];
        }

        return [
            'width' => (int) round($targetHeight * $sourceWidth / $sourceHeight),
            'height' => $targetHeight,
            'cover' => false,
        ];
    }
}
