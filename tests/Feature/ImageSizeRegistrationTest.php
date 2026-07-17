<?php

declare(strict_types=1);

it('ships the default image sizes in config', function (): void {
    $sizes = config('sproutset.image_sizes');

    expect($sizes)->toBeArray()
        ->and(array_keys($sizes))->toContain('thumbnail', 'medium', 'medium_large', 'large');
});
