<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;

it('normalizes width height and crop for a base size', function (): void {
    $result = (new ImageSizeConfigNormalizer)->normalize([
        'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
    ]);

    expect($result)->toBe([
        'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
    ]);
});

it('expands srcset multipliers into @Nx variants', function (): void {
    $result = (new ImageSizeConfigNormalizer)->normalize([
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [0.5, 2]],
    ]);

    expect($result)->toBe([
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
        'large@0.5x' => ['width' => 512, 'height' => 512, 'crop' => false],
        'large@2x' => ['width' => 2048, 'height' => 2048, 'crop' => false],
    ]);
});

it('keeps a zero base dimension at zero in variants', function (): void {
    $result = (new ImageSizeConfigNormalizer)->normalize([
        'medium_large' => ['width' => 768, 'height' => 0, 'crop' => false, 'srcset' => [2]],
    ]);

    expect($result['medium_large@2x'])->toBe(['width' => 1536, 'height' => 0, 'crop' => false]);
});

it('filters non-numeric and non-positive srcset multipliers', function (): void {
    $result = (new ImageSizeConfigNormalizer)->normalize([
        'large' => ['width' => 1000, 'height' => 0, 'crop' => false, 'srcset' => ['x', 0, -1, 2]],
    ]);

    expect(array_keys($result))->toBe(['large', 'large@2x']);
});

it('drops entries with a non-array value or non-string key', function (): void {
    $result = (new ImageSizeConfigNormalizer)->normalize([
        'ok' => ['width' => 100, 'height' => 100, 'crop' => true],
        'bad' => 'nope',
        5 => ['width' => 1, 'height' => 1, 'crop' => false],
    ]);

    expect(array_keys($result))->toBe(['ok']);
});
