<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final class AvifCleanup
{
    /**
     * Unlink the .avif siblings sproutset generated for an attachment.
     */
    public function forget(int $attachmentId): void
    {
        $file = get_attached_file($attachmentId);

        if ($file === false) {
            return;
        }

        $directory = dirname($file);
        $targets = [$file];

        $metadata = wp_get_attachment_metadata($attachmentId);

        if (is_array($metadata)) {
            foreach ($metadata['sizes'] as $size) {
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

        return substr($file, 0, -strlen($extension)).'avif';
    }
}
