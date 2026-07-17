<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\NullImageResolver;
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

it('drops empty resolved attributes', function (): void {
    bindResolver(new ResolvedImage(
        src: 'https://example.com/cat.jpg',
        srcset: null,
        sizes: null,
        width: 1200,
        height: 800,
        alt: 'A cat',
        style: null,
        isSvg: false,
    ));

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect($html)->toContain('<img')
        ->not->toContain('srcset=')
        ->not->toContain('sizes=')
        ->not->toContain('style=');
});

it('renders a reduced attribute set for an SVG source', function (): void {
    bindResolver(new ResolvedImage(
        src: 'https://example.com/logo.svg',
        srcset: 'ignored 100w',
        sizes: 'ignored',
        width: 512,
        height: 512,
        alt: 'Logo',
        style: 'object-fit: cover;',
        isSvg: true,
    ));

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect($html)->toContain('<img')
        ->toContain('src="https://example.com/logo.svg"')
        ->toContain('alt="Logo"')
        ->toContain('style="object-fit: cover;"')
        ->not->toContain('width=')
        ->not->toContain('height=')
        ->not->toContain('srcset=')
        ->not->toContain('sizes=')
        ->not->toContain('loading=')
        ->not->toContain('decoding=');
});

it('re-applies the declared class prop to the img', function (): void {
    bindResolver(rasterImage());

    $html = Blade::render('<x-sproutset-image :attachment-id="42" class="rounded shadow" />');

    expect($html)->toContain('class="rounded shadow"');
});

it('passes arbitrary attributes through the attribute bag', function (): void {
    bindResolver(rasterImage());

    $html = Blade::render('<x-sproutset-image :attachment-id="42" id="hero" data-role="banner" />');

    expect($html)->toContain('id="hero"')
        ->toContain('data-role="banner"');
});

it('renders nothing when the boot-safe null resolver is bound', function (): void {
    app()->instance(ImageResolver::class, new NullImageResolver);

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect(trim($html))->toBe('');
});

it('renders nothing when the resolved source is empty', function (): void {
    bindResolver(new ResolvedImage(
        src: null,
        srcset: null,
        sizes: null,
        width: null,
        height: null,
        alt: '',
        style: null,
        isSvg: false,
    ));

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect(trim($html))->toBe('');
});

it('applies consumer loading and decoding overrides', function (): void {
    bindResolver(rasterImage());

    $html = Blade::render('<x-sproutset-image :attachment-id="42" loading="eager" decoding="sync" />');

    expect($html)->toContain('loading="eager"')
        ->toContain('decoding="sync"');
});
