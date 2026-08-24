<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\Avif\AvifConfig;
use Webkinder\Sproutset\Images\Avif\AvifSiblingPath;
use Webkinder\Sproutset\Images\Avif\AvifVariantGenerator;

final class AvifVariantGeneratorTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        add_filter('wp_image_editors', static fn (): array => ['WP_Image_Editor_GD']);
    }

    private function generator(int $quality = 50): AvifVariantGenerator
    {
        return new AvifVariantGenerator(new AvifConfig(true, $quality));
    }

    private function serverCanWriteAvif(): bool
    {
        $editor = wp_get_image_editor(__DIR__.'/fixtures/tiny.png');

        if (is_wp_error($editor)) {
            return false;
        }

        $dest = wp_upload_dir()['path'].'/probe.avif';
        $saved = $editor->save($dest, 'image/avif');
        $ok = ! is_wp_error($saved);
        @unlink($dest);

        return $ok;
    }

    public function test_generates_avif_siblings_and_serves_them_when_supported(): void
    {
        if (! $this->serverCanWriteAvif()) {
            $this->markTestSkipped('Server image editor cannot write AVIF.');
        }

        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);

        $avif = $this->generator()->ensure($id, $source);

        $this->assertNotNull($avif);
        $this->assertFileExists($avif);
        $this->assertStringEndsWith('.avif', $avif);
    }

    public function test_discards_an_avif_that_is_not_smaller_than_the_source(): void
    {
        if (! $this->serverCanWriteAvif()) {
            $this->markTestSkipped('Server image editor cannot write AVIF.');
        }

        // A 1x1 PNG is tiny; its AVIF encoding is larger, so it must be discarded.
        $upload = wp_upload_dir();
        $source = $upload['path'].'/tiny.png';
        copy(__DIR__.'/fixtures/tiny.png', $source);
        $id = $this->seedAttachment('example.jpg'); // any real attachment id for the fuse key

        $avif = $this->generator()->ensure($id, $source);

        $this->assertNull($avif);
        $this->assertFileDoesNotExist(AvifSiblingPath::for($source, $id));
    }

    public function test_skips_an_animated_gif_source(): void
    {
        $upload = wp_upload_dir();
        $source = $upload['path'].'/animated.gif';
        copy(__DIR__.'/fixtures/animated.gif', $source);
        $id = $this->seedAttachment('example.jpg');

        $avif = $this->generator()->ensure($id, $source);

        $this->assertNull($avif);
        $this->assertFileDoesNotExist(AvifSiblingPath::for($source, $id));
    }

    public function test_returns_an_existing_avif_sibling_without_regenerating(): void
    {
        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);
        $sibling = AvifSiblingPath::for($source, $id);
        file_put_contents($sibling, 'sentinel-not-a-real-avif');

        $result = $this->generator()->ensure($id, $source);

        $this->assertSame($sibling, $result);
        $this->assertSame('sentinel-not-a-real-avif', file_get_contents($sibling)); // untouched = not regenerated
    }

    public function test_generates_distinct_avif_siblings_for_attachments_sharing_a_basename(): void
    {
        if (! $this->serverCanWriteAvif()) {
            $this->markTestSkipped('Server image editor cannot write AVIF.');
        }

        $pngId = $this->seedAttachment('example.png');
        $jpgId = $this->seedAttachment('example.jpg');

        $pngAvif = $this->generator()->ensure($pngId, get_attached_file($pngId));
        $jpgAvif = $this->generator()->ensure($jpgId, get_attached_file($jpgId));

        $this->assertNotNull($pngAvif);
        $this->assertNotNull($jpgAvif);
        $this->assertNotSame($pngAvif, $jpgAvif);
        $this->assertFileExists($pngAvif);
        $this->assertFileExists($jpgAvif);
    }

    public function test_trips_the_fuse_and_skips_retrying_after_an_encode_failure(): void
    {
        $id = $this->seedAttachment('example.jpg');

        // A non-image file with an image extension makes wp_get_image_editor fail.
        $upload = wp_upload_dir();
        $bogus = $upload['path'].'/bogus.jpg';
        file_put_contents($bogus, 'this is definitely not a JPEG');

        $first = $this->generator()->ensure($id, $bogus);
        $this->assertNull($first);
        $this->assertNotEmpty(get_post_meta($id, '_sproutset_avif_failed', true));

        $second = $this->generator()->ensure($id, $bogus);
        $this->assertNull($second);
    }
}
