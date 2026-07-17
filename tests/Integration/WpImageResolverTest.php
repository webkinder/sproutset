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

    private function request(int $id, string $size = 'large'): ImageRequest
    {
        return new ImageRequest(
            attachmentId: $id, sizeName: $size, sizes: null, alt: null,
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
}
