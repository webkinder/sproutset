<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Webkinder\Sproutset\Images\ImageResolver;
use Webkinder\Sproutset\Images\ResolvedImage;
use Webkinder\Sproutset\Tests\Support\FakeImageResolver;

function bindAvifResolver(?ResolvedImage $resolved): void
{
    app()->instance(ImageResolver::class, new FakeImageResolver($resolved));
}

it('renders a picture with an avif source when an avif srcset is present', function (): void {
    bindAvifResolver(new ResolvedImage(
        src: 'https://example.com/cat-large.jpg',
        srcset: 'https://example.com/cat-large.jpg 1200w',
        sizes: '(max-width: 1200px) 100vw, 1200px',
        width: 1200,
        height: 800,
        alt: 'A cat',
        style: null,
        isSvg: false,
        avifSrcset: 'https://example.com/cat-large.avif 1200w',
    ));

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect($html)->toContain('<picture>')
        ->toContain('<source type="image/avif" srcset="https://example.com/cat-large.avif 1200w"')
        ->toContain('sizes="(max-width: 1200px) 100vw, 1200px"')
        ->toContain('<img')
        ->toContain('src="https://example.com/cat-large.jpg"')
        ->toContain('</picture>');
});

it('renders a plain img when no avif srcset is present', function (): void {
    bindAvifResolver(new ResolvedImage(
        src: 'https://example.com/cat-large.jpg',
        srcset: 'https://example.com/cat-large.jpg 1200w',
        sizes: '1200px',
        width: 1200,
        height: 800,
        alt: 'A cat',
        style: null,
        isSvg: false,
    ));

    $html = Blade::render('<x-sproutset-image :attachment-id="42" />');

    expect($html)->toContain('<img')
        ->not->toContain('<picture>')
        ->not->toContain('image/avif');
});
