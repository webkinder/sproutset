<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

use Throwable;

final class AvifVariantGenerator
{
    private const int MAX_GENERATIONS_PER_REQUEST = 20;

    private const string FAILED_META_KEY = '_sproutset_avif_failed';

    private const string GCE_MARKER = "\x00\x21\xF9\x04";

    private int $generations = 0;

    public function __construct(private readonly AvifConfig $config) {}

    /**
     * Ensure an .avif sibling exists next to an existing subsize file. Returns
     * its absolute path, or null when skipped (already-failed attachment, cap
     * reached, animated GIF, encode failure, or a result no smaller than source).
     */
    public function ensure(int $attachmentId, string $sourceFile): ?string
    {
        $avifFile = $this->siblingPath($sourceFile);

        if ($avifFile === null) {
            return null;
        }

        if (is_file($avifFile)) {
            return $avifFile;
        }

        if ($this->generations >= self::MAX_GENERATIONS_PER_REQUEST) {
            return null;
        }

        if ($this->hasFailed($attachmentId)) {
            return null;
        }

        if (! is_file($sourceFile)) {
            return null;
        }

        if ($this->isAnimatedGif($sourceFile)) {
            return null;
        }

        $this->generations++;

        try {
            return $this->generate($attachmentId, $sourceFile, $avifFile);
        } catch (Throwable) {
            $this->markFailed($attachmentId);

            return null;
        }
    }

    private function generate(int $attachmentId, string $sourceFile, string $avifFile): ?string
    {
        $editor = wp_get_image_editor($sourceFile);

        if (is_wp_error($editor)) {
            $this->markFailed($attachmentId);

            return null;
        }

        $editor->set_quality($this->config->quality);
        $saved = $editor->save($avifFile, 'image/avif');

        if (is_wp_error($saved)) {
            $this->markFailed($attachmentId);

            return null;
        }

        $savedPath = $saved['path'];

        if (! is_file($savedPath)) {
            $this->markFailed($attachmentId);

            return null;
        }

        if ($this->notSmaller($savedPath, $sourceFile)) {
            @unlink($savedPath);

            return null;
        }

        return $savedPath;
    }

    private function notSmaller(string $avifFile, string $sourceFile): bool
    {
        $avifSize = filesize($avifFile);
        $sourceSize = filesize($sourceFile);

        if ($avifSize === false || $sourceSize === false) {
            return false;
        }

        return $avifSize >= $sourceSize;
    }

    private function siblingPath(string $file): ?string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($extension === '') {
            return null;
        }

        return substr($file, 0, -strlen($extension)).'avif';
    }

    private function isAnimatedGif(string $file): bool
    {
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'gif') {
            return false;
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            return false;
        }

        return substr_count($contents, self::GCE_MARKER) > 1;
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
