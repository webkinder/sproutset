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
        $directory = dirname($source);

        // Simulate a generated sibling next to the original file.
        $originalSibling = $this->avifSibling($source);
        file_put_contents($originalSibling, 'avif-bytes');
        $this->assertFileExists($originalSibling);

        // Simulate a generated sibling next to a subsize file from metadata.
        $metadata = wp_get_attachment_metadata($id);
        $this->assertNotEmpty($metadata['sizes'] ?? [], 'example.jpg should generate subsizes.');
        $sizeFile = reset($metadata['sizes'])['file'];
        $subsizeSibling = $this->avifSibling($directory.'/'.$sizeFile);
        file_put_contents($subsizeSibling, 'avif-bytes');
        $this->assertFileExists($subsizeSibling);

        (new AvifCleanup)->forget($id);

        $this->assertFileDoesNotExist($originalSibling);
        $this->assertFileDoesNotExist($subsizeSibling);
    }

    private function avifSibling(string $file): string
    {
        return substr($file, 0, -strlen(pathinfo($file, PATHINFO_EXTENSION))).'avif';
    }
}
