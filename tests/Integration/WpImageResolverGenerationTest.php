<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Tests\Integration;

use Webkinder\Sproutset\Attachments\WpAttachmentRepository;
use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\OnDemandSizeGenerator;
use Webkinder\Sproutset\Images\WpImageResolver;

final class WpImageResolverGenerationTest extends IntegrationTestCase
{
    private function resolver(): WpImageResolver
    {
        return new WpImageResolver(new WpAttachmentRepository, new OnDemandSizeGenerator);
    }

    private function request(int $id, string $size): ImageRequest
    {
        return new ImageRequest(
            attachmentId: $id, sizeName: $size, sizes: null, alt: null,
            width: null, height: null, class: null, loading: 'lazy',
            decoding: 'async', useAutoSizes: false, focalPoint: false,
            focalPointX: null, focalPointY: null,
        );
    }

    public function test_generates_a_missing_intermediate_size(): void
    {
        $id = $this->seedAttachment();
        add_image_size('sproutset_test', 320, 200, true);

        $resolved = $this->resolver()->resolve($this->request($id, 'sproutset_test'));

        $this->assertNotNull($resolved);
        $meta = wp_get_attachment_metadata($id);
        $this->assertArrayHasKey('sproutset_test', $meta['sizes']);
    }

    public function test_never_throws_when_generation_is_impossible(): void
    {
        $id = $this->seedAttachment();
        add_image_size('sproutset_huge', 999999, 999999, true);

        $resolved = $this->resolver()->resolve($this->request($id, 'sproutset_huge'));

        $this->assertNotNull($resolved); // degrades, does not fatal
    }
}
