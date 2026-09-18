<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\EagerGenerationDeferral;
use Webkinder\Sproutset\Images\ImageSizeConfigNormalizer;

function eagerGenerationDeferral(): EagerGenerationDeferral
{
    return new EagerGenerationDeferral(new ImageSizeConfigNormalizer);
}

/**
 * @return array<string, array<string, mixed>>
 */
function deferralRoster(): array
{
    return [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        'medium_large' => ['width' => 768, 'height' => 0, 'crop' => false, 'srcset' => [0.5, 2]],
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false, 'srcset' => [2]],
        'hero' => ['width' => 1312, 'height' => 500, 'crop' => false, 'srcset' => [1.5]],
    ];
}

it('lists only @Nx variants as deferrable, never base sizes', function (): void {
    $deferrable = eagerGenerationDeferral()->deferrableSizes(deferralRoster());

    expect($deferrable)->toBe([
        'medium_large@0.5x',
        'medium_large@2x',
        'large@2x',
        'hero@1.5x',
    ]);
});

it('removes only @Nx variants, keeping base and foreign sizes', function (): void {
    $sizes = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        'medium_large' => ['width' => 768, 'height' => 0, 'crop' => false],
        'medium_large@2x' => ['width' => 1536, 'height' => 0, 'crop' => false],
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
        'large@2x' => ['width' => 2048, 'height' => 2048, 'crop' => false],
        'hero' => ['width' => 1312, 'height' => 500, 'crop' => false],
        'hero@1.5x' => ['width' => 1968, 'height' => 750, 'crop' => false],
        'woocommerce_thumbnail' => ['width' => 300, 'height' => 300, 'crop' => true],
        'gform-image-choice-sm' => ['width' => 300, 'height' => 300, 'crop' => true],
    ];

    $result = eagerGenerationDeferral()->apply($sizes, deferralRoster());

    expect(array_keys($result))->toBe([
        'thumbnail',
        'medium',
        'medium_large',
        'large',
        'hero',
        'woocommerce_thumbnail',
        'gform-image-choice-sm',
    ]);
});

it('returns the size set unchanged for a roster without variants', function (): void {
    $sizes = [
        'woocommerce_thumbnail' => ['width' => 300, 'height' => 300, 'crop' => true],
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
    ];

    $rosterWithoutVariants = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
    ];

    expect(eagerGenerationDeferral()->apply($sizes, $rosterWithoutVariants))->toBe($sizes);
});
