<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\FocalPointPosition;
use Webkinder\Sproutset\Images\ImageRequest;

function focalRequest(bool $on, ?float $x, ?float $y): ImageRequest
{
    return new ImageRequest(
        attachmentId: 1, sizeName: 'large', sizes: null, alt: null,
        width: null, height: null, class: null, loading: 'lazy',
        decoding: 'async', useAutoSizes: true, focalPoint: $on,
        focalPointX: $x, focalPointY: $y,
    );
}

it('maps focal coordinates to an object-fit and object-position style', function (): void {
    expect(FocalPointPosition::forRequest(focalRequest(true, 25, 75)))
        ->toBe('object-fit: cover; object-position: 25% 75%;');
});

it('preserves fractional focal percentages', function (): void {
    expect(FocalPointPosition::forRequest(focalRequest(true, 33.33, 66.67)))
        ->toBe('object-fit: cover; object-position: 33.33% 66.67%;');
});

it('returns null when focal point is off', function (): void {
    expect(FocalPointPosition::forRequest(focalRequest(false, 25, 75)))->toBeNull();
});

it('returns null when a coordinate is missing', function (): void {
    expect(FocalPointPosition::forRequest(focalRequest(true, 25, null)))->toBeNull();
});
