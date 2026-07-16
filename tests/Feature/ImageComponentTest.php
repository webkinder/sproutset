<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\ResolvedImage;
use Webkinder\Sproutset\Tests\Support\FakeImageResolver;

function bindResolver(?ResolvedImage $resolved): void
{
    app()->instance(ImageResolver::class, new FakeImageResolver($resolved));
}

function rasterImage(): ResolvedImage
{
    return new ResolvedImage(
        src: 'https://example.com/cat-large.jpg',
        srcset: 'https://example.com/cat-large.jpg 1200w, https://example.com/cat-2x.jpg 2400w',
        sizes: 'auto, (max-width: 1200px) 100vw, 1200px',
        width: 1200,
        height: 800,
        alt: 'A cat',
        style: 'object-fit: cover;',
        isSvg: false,
    );
}

it('renders a raster image from the resolved view-model', function (): void {
    bindResolver(rasterImage());

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect($html)->toContain('<img')
        ->toContain('src="https://example.com/cat-large.jpg"')
        ->toContain('srcset="https://example.com/cat-large.jpg 1200w, https://example.com/cat-2x.jpg 2400w"')
        ->toContain('sizes="auto, (max-width: 1200px) 100vw, 1200px"')
        ->toContain('width="1200"')
        ->toContain('height="800"')
        ->toContain('alt="A cat"')
        ->toContain('style="object-fit: cover;"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');
});

it('renders nothing when resolution returns null', function (): void {
    bindResolver(null);

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect(trim($html))->toBe('');
});
