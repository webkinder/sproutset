<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Attachments\WpAttachmentRepository;
use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\WpImageResolver;

final class WpImageResolverTest extends IntegrationTestCase
{
    private function resolver(): WpImageResolver
    {
        return new WpImageResolver(new WpAttachmentRepository);
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
}
