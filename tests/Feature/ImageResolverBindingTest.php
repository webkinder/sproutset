<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\WpImageResolver;

it('binds the WordPress resolver as the default ImageResolver', function (): void {
    expect(resolve(ImageResolver::class))->toBeInstanceOf(WpImageResolver::class);
});
