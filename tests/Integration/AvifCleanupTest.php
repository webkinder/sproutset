<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\Avif\AvifCleanup;
use Webkinder\Sproutset\Images\Avif\AvifSiblingPath;

final class AvifCleanupTest extends IntegrationTestCase
{
    public function test_removes_avif_siblings_when_the_attachment_is_deleted(): void
    {
        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);
        $directory = dirname($source);

        // Simulate a generated sibling next to the original file.
        $originalSibling = $this->avifSibling($source, $id);
        file_put_contents($originalSibling, 'avif-bytes');
        $this->assertFileExists($originalSibling);

        // Simulate a generated sibling next to a subsize file from metadata.
        $metadata = wp_get_attachment_metadata($id);
        $this->assertNotEmpty($metadata['sizes'] ?? [], 'example.jpg should generate subsizes.');
        $sizeFile = reset($metadata['sizes'])['file'];
        $subsizeSibling = $this->avifSibling($directory.'/'.$sizeFile, $id);
        file_put_contents($subsizeSibling, 'avif-bytes');
        $this->assertFileExists($subsizeSibling);

        (new AvifCleanup)->forget($id);

        $this->assertFileDoesNotExist($originalSibling);
        $this->assertFileDoesNotExist($subsizeSibling);
    }

    public function test_does_not_remove_avif_siblings_of_another_attachment_sharing_a_basename(): void
    {
        $pngId = $this->seedAttachment('example.png');
        $jpgId = $this->seedAttachment('example.jpg');

        $pngSibling = $this->avifSibling(get_attached_file($pngId), $pngId);
        file_put_contents($pngSibling, 'avif-bytes');

        $jpgSibling = $this->avifSibling(get_attached_file($jpgId), $jpgId);
        file_put_contents($jpgSibling, 'avif-bytes');

        (new AvifCleanup)->forget($jpgId);

        $this->assertFileDoesNotExist($jpgSibling);
        $this->assertFileExists($pngSibling);
    }

    public function test_cleanup_is_safe_for_an_attachment_without_subsizes(): void
    {
        $id = self::factory()->attachment->create();
        wp_update_attachment_metadata($id, ['width' => 1, 'height' => 1]); // no 'sizes' key

        // Must not throw or warn even though there are no subsizes.
        (new AvifCleanup)->forget($id);

        $this->assertTrue(true);
    }

    private function avifSibling(string $file, int $attachmentId): ?string
    {
        return AvifSiblingPath::for($file, $attachmentId);
    }
}
