<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Attachments\WpAttachmentRepository;
use Webkinder\Sproutset\Images\Avif\AvifConfig;
use Webkinder\Sproutset\Images\Avif\AvifSupport;
use Webkinder\Sproutset\Images\Avif\AvifVariantGenerator;
use Webkinder\Sproutset\Images\FocalPointConfig;
use Webkinder\Sproutset\Images\FocalPointCropper;
use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\OnDemandSizeGenerator;
use Webkinder\Sproutset\Images\WpImageResolver;

final class AvifResolverTest extends IntegrationTestCase
{
    private function resolver(AvifConfig $config, bool $supported): WpImageResolver
    {
        $support = new class($supported) implements AvifSupport
        {
            public function __construct(private bool $supported) {}

            public function isSupported(): bool
            {
                return $this->supported;
            }
        };

        return new WpImageResolver(
            new WpAttachmentRepository,
            new OnDemandSizeGenerator,
            $support,
            new AvifVariantGenerator($config),
            $config,
            new FocalPointCropper,
            new FocalPointConfig(true),
        );
    }

    private function request(int $id, string $size = 'large'): ImageRequest
    {
        return new ImageRequest(
            attachmentId: $id,
            sizeName: $size,
            sizes: null,
            alt: null,
            width: null,
            height: null,
            class: null,
            loading: 'lazy',
            decoding: 'async',
            useAutoSizes: true,
            focalPoint: false,
            focalPointX: null,
            focalPointY: null,
        );
    }

    public function test_produces_no_avif_when_disabled(): void
    {
        $id = $this->seedAttachment('example.jpg');

        $resolved = $this->resolver(new AvifConfig(false, 50), true)->resolve($this->request($id));

        $this->assertNotNull($resolved);
        $this->assertNull($resolved->avifSrcset);
    }

    public function test_produces_no_avif_when_the_server_cannot_write_avif(): void
    {
        $id = $this->seedAttachment('example.jpg');

        $resolved = $this->resolver(new AvifConfig(true, 50), false)->resolve($this->request($id));

        $this->assertNotNull($resolved);
        $this->assertNull($resolved->avifSrcset);
    }

    public function test_builds_a_real_avif_srcset_when_enabled_and_supported(): void
    {
        add_filter('wp_image_editors', static fn (): array => ['WP_Image_Editor_GD']);

        $id = $this->seedAttachment('example.jpg');

        $resolved = $this->resolver(new AvifConfig(true, 50), true)->resolve($this->request($id, 'medium'));

        $this->assertNotNull($resolved);
        $this->assertNotNull($resolved->avifSrcset);

        $candidates = array_filter(array_map('trim', explode(',', $resolved->avifSrcset)));
        $this->assertNotEmpty($candidates);

        $foundAvifFile = false;

        foreach ($candidates as $candidate) {
            $parts = preg_split('/\s+/', $candidate, 2);
            $this->assertIsArray($parts);
            $url = $parts[0];
            $this->assertStringEndsWith('.avif', $url);

            $upload = wp_get_upload_dir();
            $path = $upload['basedir'].substr($url, strlen((string) $upload['baseurl']));

            if (is_file($path)) {
                $foundAvifFile = true;
            }
        }

        $this->assertTrue($foundAvifFile, 'expected at least one .avif file to exist on disk');
    }

    public function test_serves_avif_for_a_single_size_image_without_a_srcset(): void
    {
        add_filter('wp_image_editors', static fn (): array => ['WP_Image_Editor_GD']);
        add_image_size('sproutset_lone_crop', 800, 450, true);

        $id = $this->seedAttachment('example.jpg');

        // A unique-aspect hard crop shares its ratio with no other subsize, so
        // WordPress produces no responsive srcset for it.
        $this->assertFalse(wp_get_attachment_image_srcset($id, 'sproutset_lone_crop'));

        $resolved = $this->resolver(new AvifConfig(true, 50), true)
            ->resolve($this->request($id, 'sproutset_lone_crop'));

        $this->assertNotNull($resolved);
        $this->assertNull($resolved->srcset);
        $this->assertNotNull($resolved->avifSrcset);

        $candidates = array_filter(array_map('trim', explode(',', $resolved->avifSrcset)));
        $this->assertCount(1, $candidates);
        $this->assertStringEndsWith('.avif', $resolved->avifSrcset);

        $upload = wp_get_upload_dir();
        $path = $upload['basedir'].substr($resolved->avifSrcset, strlen((string) $upload['baseurl']));
        $this->assertFileExists($path);
    }
}
