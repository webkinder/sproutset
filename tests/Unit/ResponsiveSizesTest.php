<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageRequest;
use Webkinder\Sproutset\Images\ResponsiveSizes;

function sizesRequest(?string $sizes, bool $useAutoSizes, string $loading = 'lazy'): ImageRequest
{
    return new ImageRequest(
        attachmentId: 1, sizeName: 'large', sizes: $sizes, alt: null,
        width: null, height: null, class: null, loading: $loading,
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

it('omits auto sizes when eager loading is requested', function (): void {
    expect(ResponsiveSizes::forRequest(sizesRequest(null, true, 'eager')))->toBeNull();
});

it('still honors an explicit sizes override under eager loading', function (): void {
    expect(ResponsiveSizes::forRequest(sizesRequest('100vw', true, 'eager')))->toBe('100vw');
});
