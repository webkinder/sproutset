<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\PresentedDimensions;

it('forces the target box and requests cover for a crop smaller than its target', function (): void {
    expect(PresentedDimensions::forSource(150, 150, true, 100, 120))
        ->toBe(['width' => 150, 'height' => 150, 'cover' => true]);
});

it('keeps the crop box without cover when the source is big enough', function (): void {
    expect(PresentedDimensions::forSource(150, 150, true, 1200, 800))
        ->toBe(['width' => 150, 'height' => 150, 'cover' => false]);
});

it('upscales a width-bound non-crop size at the source ratio', function (): void {
    expect(PresentedDimensions::forSource(768, 0, false, 500, 333))
        ->toBe(['width' => 768, 'height' => 511, 'cover' => false]);
});

it('contain-fits a two-dimension non-crop size at the source ratio', function (): void {
    expect(PresentedDimensions::forSource(1024, 1024, false, 500, 400))
        ->toBe(['width' => 1024, 'height' => 819, 'cover' => false]);
});

it('contain-fits against the height for a portrait source', function (): void {
    expect(PresentedDimensions::forSource(1024, 1024, false, 1500, 2000))
        ->toBe(['width' => 768, 'height' => 1024, 'cover' => false]);
});

it('returns the box a larger source would downscale to', function (): void {
    expect(PresentedDimensions::forSource(400, 400, false, 4000, 3000))
        ->toBe(['width' => 400, 'height' => 300, 'cover' => false]);
});

it('treats a crop with no target height as width-bound', function (): void {
    expect(PresentedDimensions::forSource(600, 0, true, 500, 400))
        ->toBe(['width' => 600, 'height' => 480, 'cover' => false]);
});

it('returns null when the source dimensions are unknown', function (): void {
    expect(PresentedDimensions::forSource(1024, 1024, false, 0, 0))->toBeNull();
});

it('returns null when the target width is missing', function (): void {
    expect(PresentedDimensions::forSource(0, 0, false, 1200, 800))->toBeNull();
});
