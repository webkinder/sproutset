<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifSupport;
use Webkinder\Sproutset\Images\Avif\AvifVariantGenerator;
use Webkinder\Sproutset\Images\Avif\WpAvifSupport;

it('resolves the avif collaborators from the container', function (): void {
    expect(resolve(AvifSupport::class))->toBeInstanceOf(WpAvifSupport::class)
        ->and(resolve(AvifVariantGenerator::class))->toBeInstanceOf(AvifVariantGenerator::class);
});
