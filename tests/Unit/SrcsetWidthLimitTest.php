<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;
use Webkinder\Sproutset\Images\SrcsetWidthLimit;

function srcsetWidthLimit(): SrcsetWidthLimit
{
    return new SrcsetWidthLimit(new ImageSizeConfigNormalizer);
}

it('raises the ceiling to the largest variant wider than the WordPress default', function (): void {
    $ceiling = srcsetWidthLimit()->ceilingFor([
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [2, 3]],
    ]);

    expect($ceiling)->toBe(3072);
});

it('leaves the default in place when no configured width exceeds it', function (): void {
    $ceiling = srcsetWidthLimit()->ceilingFor([
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [2]],
    ]);

    expect($ceiling)->toBeNull();
});

it('considers a base size that itself exceeds the default', function (): void {
    $ceiling = srcsetWidthLimit()->ceilingFor([
        'hero' => ['width' => 2560, 'height' => 0, 'crop' => false],
    ]);

    expect($ceiling)->toBe(2560);
});

it('returns null for an empty config', function (): void {
    expect(srcsetWidthLimit()->ceilingFor([]))->toBeNull();
});
