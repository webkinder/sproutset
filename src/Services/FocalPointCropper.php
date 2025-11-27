<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Services;

use Spatie\Image\Image;
use Throwable;
use Webkinder\SproutsetPackage\Support\FocalPointConfig;
use Webkinder\SproutsetPackage\Support\FocalPointMetadata;
use Webkinder\SproutsetPackage\Support\ImageSizeConfigNormalizer;

final class FocalPointCropper
{
    public static function applyFocalCropToAllSizes(int $attachmentId, array $metadata): array
    {
        if (! FocalPointConfig::isEnabled()) {
            return $metadata;
        }

        if (! class_exists(Image::class)) {
            return $metadata;
        }

        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return $metadata;
        }

        $uploadsBasePath = self::getUploadsBasePath();
        $sourcePath = self::resolveSourcePath($metadata, $uploadsBasePath);

        if ($sourcePath === null) {
            return $metadata;
        }

        foreach (array_keys($metadata['sizes']) as $sizeName) {
            $metadata = self::applyFocalCropToSize(
                $attachmentId,
                $sizeName,
                $metadata,
                $sourcePath,
                $uploadsBasePath
            );
        }

        return $metadata;
    }

    public static function applyFocalCropToSingleSize(int $attachmentId, string $sizeName, array $metadata): array
    {
        if (! FocalPointConfig::isEnabled()) {
            return $metadata;
        }

        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return $metadata;
        }

        $uploadsBasePath = self::getUploadsBasePath();
        $sourcePath = self::resolveSourcePath($metadata, $uploadsBasePath);

        if ($sourcePath === null) {
            return $metadata;
        }

        return self::applyFocalCropToSize(
            $attachmentId,
            $sizeName,
            $metadata,
            $sourcePath,
            $uploadsBasePath
        );
    }

    public static function applyFocalCropToSingleSizeWithConfiguration(
        int $attachmentId,
        string $sizeName,
        array $metadata,
        int $targetWidth,
        int $targetHeight,
        bool $crop
    ): array {
        if (! FocalPointConfig::isEnabled()) {
            return $metadata;
        }

        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return $metadata;
        }

        $uploadsBasePath = self::getUploadsBasePath();
        $sourcePath = self::resolveSourcePath($metadata, $uploadsBasePath);

        if ($sourcePath === null) {
            return $metadata;
        }

        $sizeConfig = [
            'width' => $targetWidth,
            'height' => $targetHeight,
            'crop' => $crop,
        ];

        return self::applyFocalCropToSize(
            $attachmentId,
            $sizeName,
            $metadata,
            $sourcePath,
            $uploadsBasePath,
            $sizeConfig
        );
    }

    private static function resolveSizeConfigurationForName(string $sizeName): array
    {
        $sizeConfig = ImageSizeConfigNormalizer::get($sizeName);

        if ($sizeConfig !== []) {
            return $sizeConfig;
        }

        if (! preg_match('/^(?P<base>.+)@(?P<multiplier>[\d.]+)x$/', $sizeName, $matches)) {
            return [];
        }

        $baseName = $matches['base'];
        $multiplier = (float) $matches['multiplier'];

        if ($multiplier <= 0.0) {
            return [];
        }

        $baseConfig = ImageSizeConfigNormalizer::get($baseName);

        if ($baseConfig === [] || empty($baseConfig['crop']) || empty($baseConfig['width']) || empty($baseConfig['height'])) {
            return [];
        }

        $width = (int) round($baseConfig['width'] * $multiplier);
        $height = (int) round($baseConfig['height'] * $multiplier);

        if ($width <= 0 || $height <= 0) {
            return [];
        }

        return [
            'width' => $width,
            'height' => $height,
            'crop' => true,
        ];
    }

    private static function applyFocalCropToSize(
        int $attachmentId,
        string $sizeName,
        array $metadata,
        string $sourcePath,
        string $uploadsBasePath,
        ?array $sizeConfig = null
    ): array {
        if ($sizeConfig === null) {
            $sizeConfig = self::resolveSizeConfigurationForName($sizeName);
        }

        if ($sizeConfig === [] || empty($sizeConfig['crop']) || empty($sizeConfig['width']) || empty($sizeConfig['height'])) {
            return $metadata;
        }

        $targetWidth = (int) $sizeConfig['width'];
        $targetHeight = (int) $sizeConfig['height'];

        if ($targetWidth <= 0 || $targetHeight <= 0) {
            return $metadata;
        }

        $sizePath = self::resolveSizePath($metadata, $sizeName, $uploadsBasePath);

        if ($sizePath === null) {
            return $metadata;
        }

        [$xPercent, $yPercent] = self::resolveFocalPoint($attachmentId, $metadata);

        try {
            $image = Image::load($sourcePath);

            $originalWidth = $image->getWidth();
            $originalHeight = $image->getHeight();

            if (! is_int($originalWidth) || ! is_int($originalHeight) || $originalWidth <= 0 || $originalHeight <= 0) {
                return $metadata;
            }

            $dimensions = image_resize_dimensions(
                $originalWidth,
                $originalHeight,
                $targetWidth,
                $targetHeight,
                true
            );

            if ($dimensions === false) {
                return $metadata;
            }

            [, , , , $newWidth, $newHeight, $cropWidth, $cropHeight] = $dimensions;

            $fx = $xPercent / 100.0;
            $fy = $yPercent / 100.0;

            $centerX = $fx * $originalWidth;
            $centerY = $fy * $originalHeight;

            $startX = (int) round($centerX - $cropWidth / 2);
            $startY = (int) round($centerY - $cropHeight / 2);

            $startX = max(0, min($startX, $originalWidth - $cropWidth));
            $startY = max(0, min($startY, $originalHeight - $cropHeight));

            $image
                ->manualCrop($cropWidth, $cropHeight, $startX, $startY)
                ->resize($newWidth, $newHeight)
                ->save($sizePath);

            if (! isset($metadata['sizes'][$sizeName])) {
                $metadata['sizes'][$sizeName] = [];
            }

            $metadata['sizes'][$sizeName]['filesize'] = @filesize($sizePath) ?: null;
        } catch (Throwable) {
            return $metadata;
        }

        return $metadata;
    }

    private static function resolveFocalPoint(int $attachmentId, ?array $metadata = null): array
    {
        if (! is_array($metadata)) {
            $metadata = wp_get_attachment_metadata($attachmentId);
        }

        if (! is_array($metadata)) {
            $metadata = [];
        }

        return FocalPointMetadata::readCoordinatesFromMetadataArray($metadata);
    }

    private static function resolveSourcePath(array $metadata, string $uploadsBasePath): ?string
    {
        if (! isset($metadata['file'])) {
            return null;
        }

        $pathInfo = pathinfo((string) $metadata['file']);
        $directory = rtrim($uploadsBasePath.$pathInfo['dirname'], '/').'/';

        if (isset($metadata['original_image'])) {
            $originalPath = $directory.$metadata['original_image'];

            if (file_exists($originalPath)) {
                return $originalPath;
            }
        }

        $mainPath = $uploadsBasePath.$metadata['file'];

        return file_exists($mainPath) ? $mainPath : null;
    }

    private static function resolveSizePath(array $metadata, string $sizeName, string $uploadsBasePath): ?string
    {
        if (! isset($metadata['file'], $metadata['sizes'][$sizeName]['file'])) {
            return null;
        }

        $pathInfo = pathinfo((string) $metadata['file']);
        $directory = rtrim($uploadsBasePath.$pathInfo['dirname'], '/').'/';
        $filename = $metadata['sizes'][$sizeName]['file'];

        return $directory.$filename;
    }

    private static function getUploadsBasePath(): string
    {
        $uploadDir = wp_upload_dir();

        return trailingslashit($uploadDir['basedir'] ?? '');
    }
}
