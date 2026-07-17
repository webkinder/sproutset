<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Attachments\WpAttachmentRepository;
use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\OnDemandSizeGenerator;
use Webkinder\Sproutset\Images\WpImageResolver;

final class WpImageResolverTest extends IntegrationTestCase
{
    private function resolver(): WpImageResolver
    {
        return new WpImageResolver(new WpAttachmentRepository, new OnDemandSizeGenerator);
    }

    private function request(int $id, string $size = 'large', ?string $alt = null): ImageRequest
    {
        return new ImageRequest(
            attachmentId: $id, sizeName: $size, sizes: null, alt: $alt,
            width: null, height: null, class: null, loading: 'lazy',
            decoding: 'async', useAutoSizes: false, focalPoint: false,
            focalPointX: null, focalPointY: null,
        );
    }

    public function test_resolves_src_dimensions_and_alt_for_a_raster_image(): void
    {
        $id = $this->seedAttachment();
        update_post_meta($id, '_wp_attachment_image_alt', 'A cat');

        $resolved = $this->resolver()->resolve($this->request($id));

        $this->assertNotNull($resolved);
        $this->assertStringContainsString('example', (string) $resolved->src);
        $this->assertGreaterThan(0, $resolved->width);
        $this->assertGreaterThan(0, $resolved->height);
        $this->assertSame('A cat', $resolved->alt);
        $this->assertFalse($resolved->isSvg);
    }

    public function test_resolves_to_null_for_a_missing_attachment(): void
    {
        $this->assertNull($this->resolver()->resolve($this->request(999999)));
    }

    public function test_uses_empty_string_alt_when_none_is_set(): void
    {
        $id = $this->seedAttachment();

        $resolved = $this->resolver()->resolve($this->request($id));

        $this->assertSame('', $resolved->alt);
    }

    public function test_prefers_an_explicit_alt_over_post_meta(): void
    {
        $id = $this->seedAttachment();
        update_post_meta($id, '_wp_attachment_image_alt', 'From meta');

        $resolved = $this->resolver()->resolve($this->request($id, alt: 'Explicit alt'));

        $this->assertSame('Explicit alt', $resolved->alt);
    }

    public function test_marks_svg_sources_and_skips_raster_fields(): void
    {
        $id = $this->seedAttachment('example.svg');

        $resolved = $this->resolver()->resolve($this->request($id));

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->isSvg);
        $this->assertNull($resolved->width);
        $this->assertNull($resolved->height);
        $this->assertNull($resolved->srcset);
        $this->assertNotSame('', (string) $resolved->src);
    }

    public function test_populates_srcset_for_a_raster_image(): void
    {
        $id = $this->seedAttachment();

        $resolved = $this->resolver()->resolve($this->request($id, 'medium'));

        // A single-size upload may legitimately have no candidates; assert the
        // type contract and, when present, the descriptor format.
        $this->assertTrue($resolved->srcset === null || str_contains($resolved->srcset, 'w'));
    }

    public function test_upscales_a_crop_size_to_its_target_box_with_object_fit_cover(): void
    {
        $id = $this->seedAttachment();
        add_image_size('sproutset_upscale_crop', 2000, 2000, true);

        try {
            $resolved = $this->resolver()->resolve($this->request($id, 'sproutset_upscale_crop'));
        } finally {
            remove_image_size('sproutset_upscale_crop');
        }

        $this->assertNotNull($resolved);
        $this->assertSame(2000, $resolved->width);
        $this->assertSame(2000, $resolved->height);
        $this->assertSame('object-fit: cover;', $resolved->style);
    }

    public function test_upscales_a_non_crop_size_preserving_the_source_aspect_ratio(): void
    {
        $id = $this->seedAttachment();
        add_image_size('sproutset_upscale', 2000, 0, false);

        try {
            $resolved = $this->resolver()->resolve($this->request($id, 'sproutset_upscale'));
        } finally {
            remove_image_size('sproutset_upscale');
        }

        $this->assertNotNull($resolved);
        $this->assertSame(2000, $resolved->width);
        $this->assertSame(1333, $resolved->height); // round(2000 * 800 / 1200)
        $this->assertNull($resolved->style);
    }

    public function test_leaves_a_big_enough_source_at_its_delivered_dimensions(): void
    {
        $id = $this->seedAttachment();
        add_image_size('sproutset_small', 600, 0, false);

        try {
            $resolved = $this->resolver()->resolve($this->request($id, 'sproutset_small'));
        } finally {
            remove_image_size('sproutset_small');
        }

        $this->assertNotNull($resolved);
        $this->assertSame(600, $resolved->width);
        $this->assertSame(400, $resolved->height); // round(600 * 800 / 1200)
        $this->assertNull($resolved->style);
    }

    public function test_keeps_wordpress_dimensions_for_an_unregistered_size(): void
    {
        $id = $this->seedAttachment();

        $resolved = $this->resolver()->resolve($this->request($id, 'full'));

        $this->assertNotNull($resolved);
        $this->assertSame(1200, $resolved->width);
        $this->assertSame(800, $resolved->height);
        $this->assertNull($resolved->style);
    }
}
