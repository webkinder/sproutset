<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Attachments;

interface AttachmentRepository
{
    public function find(int $id): ?Attachment;
}
