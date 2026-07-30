<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

use Throwable;

final class FocalPointCropper
{
    private const int MAX_CROPS_PER_REQUEST = 20;

    private const string FAILED_META_KEY = '_sproutset_focal_crop_failed';

    private int $crops = 0;

    public static function clearFuse(int $attachmentId): void
    {
        delete_post_meta($attachmentId, self::FAILED_META_KEY);
    }

    public function ensureForAttachment(int $attachmentId, FocalPoint $focal): void
    {
        try {
            $this->run($attachmentId, $focal);
        } catch (Throwable) {
            // Boot-safety: never fatal a request over focal cropping.
            $this->markFailed($attachmentId);
        }
    }

    private function run(int $attachmentId, FocalPoint $focal): void
    {
        if ($this->hasFailed($attachmentId)) {
            return;
        }

        $rawMetadata = wp_get_attachment_metadata($attachmentId);

        /** @var array<string, mixed> $metadata */
        $metadata = is_array($rawMetadata) ? $rawMetadata : [];

        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return;
        }

        $originalWidth = is_numeric($metadata['width'] ?? null) ? (int) $metadata['width'] : 0;
        $originalHeight = is_numeric($metadata['height'] ?? null) ? (int) $metadata['height'] : 0;

        if ($originalWidth <= 0 || $originalHeight <= 0) {
            return;
        }

        $file = get_attached_file($attachmentId);

        if ($file === false || ! file_exists($file)) {
            return;
        }

        $directory = dirname($file);

        /** @var array<string, mixed> $registered */
        $registered = wp_get_registered_image_subsizes();
        $signature = $focal->x.','.$focal->y;
        $applied = FocalPointMeta::appliedAt($attachmentId);

        foreach ($metadata['sizes'] as $sizeName => $size) {
            if ($this->crops >= self::MAX_CROPS_PER_REQUEST) {
                return;
            }

            if (! is_string($sizeName)) {
                continue;
            }

            if (! isset($registered[$sizeName])) {
                continue;
            }

            if (! is_array($registered[$sizeName])) {
                continue;
            }

            $spec = $registered[$sizeName];

            if (empty($spec['crop'])) {
                continue;
            }

            if (($applied[$sizeName] ?? null) === $signature) {
                continue;
            }

            if (! is_array($size)) {
                continue;
            }

            if (! isset($size['file'])) {
                continue;
            }

            if (! is_string($size['file'])) {
                continue;
            }

            $targetWidth = is_numeric($spec['width'] ?? null) ? (int) $spec['width'] : 0;
            $targetHeight = is_numeric($spec['height'] ?? null) ? (int) $spec['height'] : 0;

            $window = FocalCropWindow::forTarget($originalWidth, $originalHeight, $targetWidth, $targetHeight, $focal);

            if ($window === null) {
                continue;
            }

            $this->crops++;

            $destination = $directory.'/'.$size['file'];

            if (! $this->cropTo($file, $destination, $window, $targetWidth, $targetHeight)) {
                $this->markFailed($attachmentId);

                return;
            }

            $this->purgeAvifSibling($destination);

            FocalPointMeta::markApplied($attachmentId, $sizeName, $signature);
        }
    }

    /**
     * @param  array{x: int, y: int, w: int, h: int}  $window
     */
    private function cropTo(string $source, string $destination, array $window, int $width, int $height): bool
    {
        $editor = wp_get_image_editor($source);

        if (is_wp_error($editor)) {
            return false;
        }

        if (is_wp_error($editor->crop($window['x'], $window['y'], $window['w'], $window['h'], $width, $height))) {
            return false;
        }

        return ! is_wp_error($editor->save($destination));
    }

    private function purgeAvifSibling(string $file): void
    {
        $sibling = $this->avifSiblingPath($file);

        if ($sibling !== null && is_file($sibling)) {
            @unlink($sibling);
        }
    }

    private function avifSiblingPath(string $file): ?string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($extension === '') {
            return null;
        }

        return mb_substr($file, 0, -mb_strlen($extension)).'avif';
    }

    private function hasFailed(int $attachmentId): bool
    {
        return (bool) get_post_meta($attachmentId, self::FAILED_META_KEY, true);
    }

    private function markFailed(int $attachmentId): void
    {
        update_post_meta($attachmentId, self::FAILED_META_KEY, 1);
    }
}
