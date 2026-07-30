<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\CoreImageSizeOptions;
use Webkinder\Sproutset\Images\MediaSettingsLock;

it('resolves the core image size collaborators from the container', function (): void {
    expect(resolve(CoreImageSizeOptions::class))->toBeInstanceOf(CoreImageSizeOptions::class)
        ->and(resolve(MediaSettingsLock::class))->toBeInstanceOf(MediaSettingsLock::class);
});
