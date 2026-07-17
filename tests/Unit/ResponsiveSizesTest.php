<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\ResponsiveSizes;

function sizesRequest(?string $sizes, bool $useAutoSizes): ImageRequest
{
    return new ImageRequest(
        attachmentId: 1, sizeName: 'large', sizes: $sizes, alt: null,
        width: null, height: null, class: null, loading: 'lazy',
        decoding: 'async', useAutoSizes: $useAutoSizes, focalPoint: false,
        focalPointX: null, focalPointY: null,
    );
}

it('prefers an explicit sizes override', function (): void {
    expect(ResponsiveSizes::forRequest(sizesRequest('(max-width: 600px) 480px, 800px', true)))
        ->toBe('(max-width: 600px) 480px, 800px');
});

it('emits auto when auto sizes are enabled and no override is given', function (): void {
    expect(ResponsiveSizes::forRequest(sizesRequest(null, true)))->toBe('auto');
});

it('emits null when neither an override nor auto sizes apply', function (): void {
    expect(ResponsiveSizes::forRequest(sizesRequest(null, false)))->toBeNull();
});
