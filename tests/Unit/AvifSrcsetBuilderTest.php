<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifSrcsetBuilder;

it('maps candidates with existing siblings to avif urls at the same descriptor', function (): void {
    $original = 'https://x/img-300x200.jpg 300w, https://x/img-768x512.jpg 768w';

    $siblingFor = fn (string $url): ?string => str_contains($url, '300x200')
        ? 'https://x/img-300x200.avif'
        : null;

    expect(AvifSrcsetBuilder::build($original, $siblingFor))
        ->toBe('https://x/img-300x200.avif 300w');
});

it('preserves a candidate that has no descriptor', function (): void {
    $siblingFor = fn (string $url): string => 'https://x/only.avif';

    expect(AvifSrcsetBuilder::build('https://x/only.jpg', $siblingFor))
        ->toBe('https://x/only.avif');
});

it('returns null when no sibling exists', function (): void {
    expect(AvifSrcsetBuilder::build('https://x/a.jpg 1x', fn (): ?string => null))
        ->toBeNull();
});
