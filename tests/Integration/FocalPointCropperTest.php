<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\FocalPoint;
use Webkinder\Sproutset\Images\FocalPointCropper;
use Webkinder\Sproutset\Images\FocalPointMeta;

final class FocalPointCropperTest extends IntegrationTestCase
{
    public function test_crops_hard_crop_subsizes_and_marks_them_applied(): void
    {
        $id = $this->seedAttachment(); // example.jpg -> WP core 'thumbnail' (150x150, hard crop)
        $metadata = wp_get_attachment_metadata($id);
        $this->assertArrayHasKey('thumbnail', $metadata['sizes'] ?? []);

        (new FocalPointCropper)->ensureForAttachment($id, new FocalPoint(25.0, 75.0));

        $applied = FocalPointMeta::appliedAt($id);
        $this->assertSame('25,75', $applied['thumbnail'] ?? null);

        // The cropped file is still a valid 150x150 image.
        $thumbPath = dirname(get_attached_file($id)).'/'.$metadata['sizes']['thumbnail']['file'];
        $dimensions = getimagesize($thumbPath);
        $this->assertSame(150, $dimensions[0]);
        $this->assertSame(150, $dimensions[1]);
    }

    public function test_skips_subsizes_already_applied_at_the_current_point(): void
    {
        $id = $this->seedAttachment();
        FocalPointMeta::markApplied($id, 'thumbnail', '25,75');

        $before = filemtime(dirname(get_attached_file($id)).'/'.wp_get_attachment_metadata($id)['sizes']['thumbnail']['file']);

        (new FocalPointCropper)->ensureForAttachment($id, new FocalPoint(25.0, 75.0));

        $after = filemtime(dirname(get_attached_file($id)).'/'.wp_get_attachment_metadata($id)['sizes']['thumbnail']['file']);
        $this->assertSame($before, $after, 'An already-applied subsize must not be re-cropped.');
        $this->assertSame('25,75', FocalPointMeta::appliedAt($id)['thumbnail'] ?? null);
    }

    public function test_does_not_crop_or_mark_when_the_fuse_is_tripped(): void
    {
        $id = $this->seedAttachment();
        update_post_meta($id, '_sproutset_focal_crop_failed', 1);

        (new FocalPointCropper)->ensureForAttachment($id, new FocalPoint(25.0, 75.0));

        $this->assertSame([], FocalPointMeta::appliedAt($id));
    }

    public function test_purges_the_avif_sibling_of_a_re_cropped_subsize(): void
    {
        $id = $this->seedAttachment();
        $metadata = wp_get_attachment_metadata($id);
        $thumbPath = dirname(get_attached_file($id)).'/'.$metadata['sizes']['thumbnail']['file'];
        $sibling = $this->avifSibling($thumbPath);
        file_put_contents($sibling, 'avif-bytes');
        $this->assertFileExists($sibling);

        (new FocalPointCropper)->ensureForAttachment($id, new FocalPoint(25.0, 75.0));

        $this->assertFileDoesNotExist($sibling);
    }

    public function test_does_not_mark_a_registered_non_crop_size_as_applied(): void
    {
        $id = $this->seedAttachment();
        $metadata = wp_get_attachment_metadata($id);
        $this->assertArrayHasKey('medium', $metadata['sizes'] ?? []);

        (new FocalPointCropper)->ensureForAttachment($id, new FocalPoint(25.0, 75.0));

        $this->assertArrayNotHasKey('medium', FocalPointMeta::appliedAt($id));
    }

    private function avifSibling(string $file): string
    {
        return substr($file, 0, -strlen(pathinfo($file, PATHINFO_EXTENSION))).'avif';
    }
}
