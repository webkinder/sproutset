<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Attachments;

/**
 * Boundary between Sproutset and WordPress attachment retrieval.
 *
 * Every call into WordPress' media functions goes through an implementation of
 * this contract, so the component logic can be exercised against an in-memory
 * fake with zero WordPress runtime. The real implementation is the only code
 * that needs a live WordPress and the `wp-phpunit` integration lane.
 */
interface AttachmentRepository
{
    /**
     * Resolve an attachment by its WordPress ID, or null when it does not exist.
     */
    public function find(int $id): ?Attachment;
}
