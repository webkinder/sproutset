<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

use Throwable;

final class AvifCleanup
{
    public function forget(int $attachmentId): void
    {
        try {
            $this->doForget($attachmentId);
        } catch (Throwable) {
            // Cleanup must never fatal a delete.
        }
    }

    private function doForget(int $attachmentId): void
    {
        $file = get_attached_file($attachmentId);

        if ($file === false) {
            return;
        }

        $directory = dirname($file);
        $targets = [$file];

        $metadata = wp_get_attachment_metadata($attachmentId);

        /** @var array<string, mixed> $data */
        $data = is_array($metadata) ? $metadata : [];
        $sizes = $data['sizes'] ?? [];

        if (is_array($sizes)) {
            foreach ($sizes as $size) {
                if (is_array($size) && isset($size['file']) && is_string($size['file'])) {
                    $targets[] = $directory.'/'.$size['file'];
                }
            }
        }

        foreach ($targets as $target) {
            $sibling = $this->siblingPath($target);

            if ($sibling !== null && is_file($sibling)) {
                @unlink($sibling);
            }
        }
    }

    private function siblingPath(string $file): ?string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($extension === '') {
            return null;
        }

        return mb_substr($file, 0, -mb_strlen($extension)).'avif';
    }
}
