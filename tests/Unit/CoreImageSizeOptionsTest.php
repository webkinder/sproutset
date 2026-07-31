<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\CoreImageSizeOptions;
use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;

function coreImageSizeOptions(): CoreImageSizeOptions
{
    return new CoreImageSizeOptions(new ImageSizeConfigNormalizer);
}

it('maps a core size to its width height and crop options', function (): void {
    $overrides = coreImageSizeOptions()->overrides([
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
    ]);

    expect($overrides)->toMatchArray([
        'thumbnail_size_w' => 150,
        'thumbnail_size_h' => 150,
        'thumbnail_crop' => 1,
    ]);
});

it('maps crop to one or zero', function (): void {
    $cropping = coreImageSizeOptions()->overrides([
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
    ]);
    $soft = coreImageSizeOptions()->overrides([
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => false],
    ]);

    expect($cropping['thumbnail_crop'])->toBe(1)
        ->and($soft['thumbnail_crop'])->toBe(0);
});

it('skips a core size absent from config', function (): void {
    $overrides = coreImageSizeOptions()->overrides([
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
    ]);

    expect($overrides)->not->toHaveKey('large_size_w')
        ->and($overrides)->not->toHaveKey('large_size_h');
});

it('ignores custom sizes and variants', function (): void {
    $overrides = coreImageSizeOptions()->overrides([
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        'medium_large' => ['width' => 768, 'height' => 0, 'crop' => false],
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [2]],
        'sproutset_card' => ['width' => 600, 'height' => 400, 'crop' => true],
    ]);

    expect(array_keys($overrides))->toBe([
        'thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop',
        'medium_size_w', 'medium_size_h',
        'medium_large_size_w', 'medium_large_size_h',
        'large_size_w', 'large_size_h',
    ]);
});
