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

        $attachment = wp_get_attachment_image_src($id, 'full');

        $url = $attachment[0];
        $width = $attachment[1];
        $height = $attachment[2];

        return new Attachment($id, $url, $width, $height);
    }
}
