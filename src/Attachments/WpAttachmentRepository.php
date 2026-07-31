<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Attachments;

final class WpAttachmentRepository implements AttachmentRepository
{
    public function find(int $id): ?Attachment
    {
        if (! wp_attachment_is_image($id)) {
            return null;
        }

        $source = wp_get_attachment_image_src($id, 'full');

        if ($source === false) {
            return null;
        }

        return new Attachment($id, $source[0], $source[1], $source[2]);
    }
}
