<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Support;

use Webkinder\Sproutset\Attachments\Attachment;
use Webkinder\Sproutset\Attachments\AttachmentRepository;

/**
 * In-memory {@see AttachmentRepository} for the fast Testbench lane.
 *
 * Feature tests seed attachments with {@see self::add()} instead of a live
 * WordPress. The real WordPress-backed implementation must satisfy the same
 * behaviour — pin both to it with a shared contract test when it lands.
 */
final class FakeAttachmentRepository implements AttachmentRepository
{
    /**
     * @var array<int, Attachment>
     */
    private array $attachments = [];

    public function add(Attachment $attachment): void
    {
        $this->attachments[$attachment->id] = $attachment;
    }

    public function find(int $id): ?Attachment
    {
        return $this->attachments[$id] ?? null;
    }
}
