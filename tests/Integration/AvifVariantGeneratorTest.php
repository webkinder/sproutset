<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Images\Avif\AvifConfig;
use Webkinder\Sproutset\Images\Avif\AvifSiblingPath;
use Webkinder\Sproutset\Images\Avif\AvifSignature;
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
        $sentinel = "\x00\x00\x00\x14ftypavif\x00\x00\x00\x00avif".'sentinel';
        file_put_contents($sibling, $sentinel);

        $result = $this->generator()->ensure($id, $source);

        $this->assertSame($sibling, $result);
        $this->assertSame($sentinel, file_get_contents($sibling)); // untouched = not regenerated
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidSiblingContents(): array
    {
        return [
            'empty' => [''],
            'non-empty without an AVIF signature' => ["\x89PNG\r\n\x1a\nnot an avif"],
        ];
    }

    /**
     * @dataProvider invalidSiblingContents
     */
    public function test_regenerates_an_invalid_avif_sibling(string $invalidContents): void
    {
        if (! $this->serverCanWriteAvif()) {
            $this->markTestSkipped('Server image editor cannot write AVIF.');
        }

        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);
        $sibling = AvifSiblingPath::for($source, $id);
        file_put_contents($sibling, $invalidContents);

        $result = $this->generator()->ensure($id, $source);

        $this->assertSame($sibling, $result);
        $this->assertTrue(AvifSignature::isAvif((string) file_get_contents($sibling)));
    }

    public function test_writes_the_avif_sibling_atomically(): void
    {
        if (! $this->serverCanWriteAvif()) {
            $this->markTestSkipped('Server image editor cannot write AVIF.');
        }

        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);
        $sibling = AvifSiblingPath::for($source, $id);
        file_put_contents($sibling, 'stale');

        $encodeTargets = [];
        $siblingDuringEncode = null;
        add_filter('image_editor_output_format', static function (array $formats, ?string $filename) use (&$encodeTargets, &$siblingDuringEncode, $sibling): array {
            $encodeTargets[] = $filename;
            $siblingDuringEncode = file_get_contents($sibling);

            return $formats;
        }, 10, 2);

        $result = $this->generator()->ensure($id, $source);

        $this->assertSame($sibling, $result);
        $this->assertNotContains($sibling, $encodeTargets);
        $this->assertSame('stale', $siblingDuringEncode);
        $this->assertTrue(AvifSignature::isAvif((string) file_get_contents($sibling)));
        $this->assertSame([$sibling], glob(dirname($sibling).'/'.basename($sibling, '.avif').'*'));
    }

    public function test_keeps_an_invalid_sibling_unserved_when_regeneration_is_blocked(): void
    {
        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);
        $sibling = AvifSiblingPath::for($source, $id);
        file_put_contents($sibling, '');
        update_post_meta($id, '_sproutset_avif_failed', 1);

        $result = $this->generator()->ensure($id, $source);

        $this->assertNull($result);
        $this->assertFileExists($sibling);
    }

    public function test_trips_the_fuse_when_the_encoded_file_is_not_a_valid_avif(): void
    {
        $id = $this->seedAttachment('example.jpg');
        $source = get_attached_file($id);
        $sibling = AvifSiblingPath::for($source, $id);
        add_filter('image_editor_output_format', static fn (array $formats): array => ['image/avif' => 'image/png'] + $formats);

        $result = $this->generator()->ensure($id, $source);

        $this->assertNull($result);
        $this->assertNotEmpty(get_post_meta($id, '_sproutset_avif_failed', true));
        $this->assertSame([], glob(dirname($sibling).'/'.basename($sibling, '.avif').'*'));
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
