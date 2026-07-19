<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final class AvifCleanup
{
    /**
     * Unlink every .avif sibling belonging to an attachment. Siblings are
     * sproutset-owned and absent from WP metadata, so WP will not remove them.
     * Runs before WP deletes the originals, so the files are still present.
     */
    public function forget(int $attachmentId): void
    {
        $file = get_attached_file($attachmentId);

        if ($file === false) {
            return;
        }

        $directory = dirname($file);
        $base = pathinfo($file, PATHINFO_FILENAME);

        $siblings = glob($directory.'/'.$base.'*.avif');

        if ($siblings === false) {
            return;
        }

        foreach ($siblings as $sibling) {
            @unlink($sibling);
        }
    }
}
