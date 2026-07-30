<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifConfig;

it('resolves an AvifConfig from the container defaulting to disabled', function (): void {
    $config = resolve(AvifConfig::class);

    expect($config)->toBeInstanceOf(AvifConfig::class)
        ->and($config->enabled)->toBeFalse()
        ->and($config->quality)->toBe(50);
});
