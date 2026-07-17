<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageSizeRegistrar;
use Webkinder\Sproutset\Tests\TestCase;

it('ships the default image sizes in config', function (): void {
    /** @var array<string, mixed> $sizes */
    $sizes = config('sproutset.image_sizes');

    expect($sizes)->toBeArray()
        ->and(array_keys($sizes))->toContain('thumbnail', 'medium', 'medium_large', 'large');
});

it('resolves the image size registrar from the container', function (): void {
    /** @var TestCase $this */
    expect($this->app->make(ImageSizeRegistrar::class))->toBeInstanceOf(ImageSizeRegistrar::class);
});
