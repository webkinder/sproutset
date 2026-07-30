<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\FocalPointConfig;

it('defaults the focal point feature to enabled', function (): void {
    expect(resolve(FocalPointConfig::class)->enabled)->toBeTrue();
});

it('reflects a disabled focal point config flag', function (): void {
    config()->set('sproutset.focal_point', false);

    expect(resolve(FocalPointConfig::class)->enabled)->toBeFalse();
});
