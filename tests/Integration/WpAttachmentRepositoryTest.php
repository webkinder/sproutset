<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Attachments\WpAttachmentRepository;

final class WpAttachmentRepositoryTest extends IntegrationTestCase
{
    public function test_resolves_a_real_attachment_by_id(): void
    {
        $id = $this->seedAttachment();

        $attachment = (new WpAttachmentRepository)->find($id);

        $this->assertNotNull($attachment);
        $this->assertSame($id, $attachment->id);
        $this->assertNotSame('', $attachment->url);
        $this->assertSame(1200, $attachment->width);
        $this->assertSame(800, $attachment->height);
    }

    public function test_returns_null_for_an_unknown_id(): void
    {
        $this->assertNull((new WpAttachmentRepository)->find(999999));
    }

    public function test_returns_null_for_a_non_image_post(): void
    {
        $postId = self::factory()->post->create();

        $this->assertNull((new WpAttachmentRepository)->find($postId));
    }
}
