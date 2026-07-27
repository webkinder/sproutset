<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\FocalCropWindow;
use Webkinder\Sproutset\Images\FocalPoint;

it('matches the target aspect and centres on the focal point', function (): void {
    // 1000x500 original, square (200x200) target -> window is 500x500 (largest square).
    $window = FocalCropWindow::forTarget(1000, 500, 200, 200, new FocalPoint(25.0, 75.0));

    // centreX = 250 -> x = 250 - 250 = 0; centreY = 375 -> y = 375 - 250 = 125, clamped to 0.
    expect($window)->toBe(['x' => 0, 'y' => 0, 'w' => 500, 'h' => 500]);
});

it('centres a square target within a wide original at 50/50', function (): void {
    $window = FocalCropWindow::forTarget(1000, 500, 200, 200, new FocalPoint(50.0, 50.0));

    // centreX = 500 -> x = 500 - 250 = 250; centreY = 250 -> y = 0 (clamped).
    expect($window)->toBe(['x' => 250, 'y' => 0, 'w' => 500, 'h' => 500]);
});

it('clamps the window origin to the original bounds at the far corner', function (): void {
    $window = FocalCropWindow::forTarget(1000, 500, 200, 200, new FocalPoint(100.0, 100.0));

    // x = 1000 - 250 = 750, clamped to originalWidth - w = 500; y clamped to 0.
    expect($window)->toBe(['x' => 500, 'y' => 0, 'w' => 500, 'h' => 500]);
});

it('returns null for non-positive dimensions', function (): void {
    expect(FocalCropWindow::forTarget(0, 500, 200, 200, new FocalPoint(50.0, 50.0)))->toBeNull()
        ->and(FocalCropWindow::forTarget(1000, 500, 200, 0, new FocalPoint(50.0, 50.0)))->toBeNull();
});
