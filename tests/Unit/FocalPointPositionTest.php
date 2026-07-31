<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\FocalPoint;
use Webkinder\Sproutset\Images\FocalPointPosition;

it('maps an off-center focal point in a cover context to object-fit and object-position', function (): void {
    expect(FocalPointPosition::forCover(new FocalPoint(25.0, 75.0), true))
        ->toBe('object-fit: cover; object-position: 25% 75%;');
});

it('preserves fractional focal percentages', function (): void {
    expect(FocalPointPosition::forCover(new FocalPoint(33.33, 66.67), true))
        ->toBe('object-fit: cover; object-position: 33.33% 66.67%;');
});

it('emits plain object-fit cover for a center focal point in a cover context', function (): void {
    expect(FocalPointPosition::forCover(new FocalPoint(50.0, 50.0), true))
        ->toBe('object-fit: cover;');
});

it('emits plain object-fit cover when there is no focal point but cover is in play', function (): void {
    expect(FocalPointPosition::forCover(null, true))->toBe('object-fit: cover;');
});

it('returns null when cover is not in play', function (): void {
    expect(FocalPointPosition::forCover(new FocalPoint(25.0, 75.0), false))->toBeNull()
        ->and(FocalPointPosition::forCover(null, false))->toBeNull();
});
