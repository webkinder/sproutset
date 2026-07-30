<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\FocalPoint;

it('exposes its coordinates', function (): void {
    $focal = new FocalPoint(25.0, 75.0);

    expect($focal->x)->toBe(25.0)
        ->and($focal->y)->toBe(75.0);
});

it('is center at exactly 50/50', function (): void {
    expect(new FocalPoint(50.0, 50.0)->isCenter())->toBeTrue();
});

it('is not center when either coordinate differs', function (): void {
    expect(new FocalPoint(50.0, 49.9)->isCenter())->toBeFalse()
        ->and(new FocalPoint(10.0, 50.0)->isCenter())->toBeFalse();
});
