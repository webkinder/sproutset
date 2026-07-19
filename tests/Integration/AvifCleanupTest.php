<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\Avif\AvifCleanup;

final class AvifCleanupTest extends IntegrationTestCase
{
    public function test_removes_avif_siblings_when_the_attachment_is_deleted(): void
    {
        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);

        // Simulate a generated sibling next to the original file.
        $sibling = substr($source, 0, -strlen(pathinfo($source, PATHINFO_EXTENSION))).'avif';
        file_put_contents($sibling, 'avif-bytes');
        $this->assertFileExists($sibling);

        (new AvifCleanup)->forget($id);

        $this->assertFileDoesNotExist($sibling);
    }
}
