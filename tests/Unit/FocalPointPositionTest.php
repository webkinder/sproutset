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

it('maps focal coordinates to an object-position style', function (): void {
    expect(FocalPointPosition::forRequest(focalRequest(true, 0.25, 0.75)))
        ->toBe('object-position: 25% 75%;');
});

it('returns null when focal point is off', function (): void {
    expect(FocalPointPosition::forRequest(focalRequest(false, 0.25, 0.75)))->toBeNull();
});

it('returns null when a coordinate is missing', function (): void {
    expect(FocalPointPosition::forRequest(focalRequest(true, 0.25, null)))->toBeNull();
});
