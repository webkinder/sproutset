<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

use Throwable;

final class AvifVariantGenerator
{
    private const int MAX_GENERATIONS_PER_REQUEST = 20;

    private const string FAILED_META_KEY = '_sproutset_avif_failed';

    private const string GCE_MARKER = "\x00\x21\xF9\x04";

    private const int SIGNATURE_PROBE_BYTES = 64;

    private int $generations = 0;

    public function __construct(private readonly AvifConfig $config) {}

    public function ensure(int $attachmentId, string $sourceFile): ?string
    {
        $avifFile = AvifSiblingPath::for($sourceFile, $attachmentId);

        if ($avifFile === null) {
            return null;
        }

        if ($this->isValidAvif($avifFile)) {
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
        $saved = $editor->save($this->temporaryPathFor($avifFile), 'image/avif');

        if (is_wp_error($saved)) {
            $this->markFailed($attachmentId);

            return null;
        }

        $savedPath = $saved['path'];

        try {
            if (! $this->isValidAvif($savedPath)) {
                $this->markFailed($attachmentId);

                return null;
            }

            if ($this->notSmaller($savedPath, $sourceFile)) {
                return null;
            }

            return @rename($savedPath, $avifFile) ? $avifFile : null;
        } finally {
            if (is_file($savedPath)) {
                @unlink($savedPath);
            }
        }
    }

    private function temporaryPathFor(string $avifFile): string
    {
        return $avifFile.'.tmp-'.bin2hex(random_bytes(6)).'.avif';
    }

    /**
     * Whether the file exists and starts with an AVIF signature.
     */
    private function isValidAvif(string $file): bool
    {
        if (! is_file($file)) {
            return false;
        }

        $header = @file_get_contents($file, false, null, 0, self::SIGNATURE_PROBE_BYTES);

        return $header !== false && AvifSignature::isAvif($header);
    }

    private function notSmaller(string $avifFile, string $sourceFile): bool
    {
        $avifSize = filesize($avifFile);
        $sourceSize = filesize($sourceFile);

        if ($avifSize === false || $sourceSize === false) {
            return true;
        }

        return $avifSize >= $sourceSize;
    }

    private function isAnimatedGif(string $file): bool
    {
        if (mb_strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'gif') {
            return false;
        }

        $contents = file_get_contents($file);

        if ($contents === false) {
            return false;
        }

        return mb_substr_count($contents, self::GCE_MARKER, '8bit') > 1;
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
