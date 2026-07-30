<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;
use Webkinder\Sproutset\Images\ImageSizeRegistrar;

it('ships the default image sizes in config', function (): void {
    /** @var array<string, mixed> $sizes */
    $sizes = config('sproutset.image_sizes');

    expect($sizes)->toBeArray()
        ->and(array_keys($sizes))->toContain('thumbnail', 'medium', 'medium_large', 'large');
});

it('resolves the image size registrar from the container', function (): void {
    expect(resolve(ImageSizeRegistrar::class))->toBeInstanceOf(ImageSizeRegistrar::class);
});

it('normalizes the shipped default config into base sizes and variants', function (): void {
    /** @var array<string, mixed> $defaults */
    $defaults = config('sproutset.image_sizes');

    $normalized = (new ImageSizeConfigNormalizer)->normalize($defaults);

    expect(array_keys($normalized))->toContain(
        'thumbnail', 'medium', 'medium_large', 'large',
        'medium_large@0.5x', 'medium_large@2x', 'large@0.5x', 'large@2x',
    )
        ->and($normalized['large@2x'])->toBe(['width' => 2048, 'height' => 2048, 'crop' => false])
        ->and($normalized['medium_large@2x'])->toBe(['width' => 1536, 'height' => 0, 'crop' => false]);
});
