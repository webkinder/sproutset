<?php

declare(strict_types=1);

use Webkinder\Sproutset\SproutsetServiceProvider;

it('registers the sproutset service provider', function (): void {
    expect(app()->providerIsLoaded(SproutsetServiceProvider::class))->toBeTrue();
});

it('merges the sproutset config file', function (): void {
    expect(config('sproutset'))->toBeArray();
});
