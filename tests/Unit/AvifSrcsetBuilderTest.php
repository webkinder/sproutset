<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifSrcsetBuilder;

it('maps every candidate to its avif sibling url at the same descriptor when all siblings exist', function (): void {
    $original = 'https://x/img-300x200.jpg 300w, https://x/img-768x512.jpg 768w';

    $siblingFor = fn (string $url): string => str_replace('.jpg', '.avif', $url);

    expect(AvifSrcsetBuilder::build($original, $siblingFor))
        ->toBe('https://x/img-300x200.avif 300w, https://x/img-768x512.avif 768w');
});

it('preserves a candidate that has no descriptor', function (): void {
    $siblingFor = fn (string $url): string => 'https://x/only.avif';

    expect(AvifSrcsetBuilder::build('https://x/only.jpg', $siblingFor))
        ->toBe('https://x/only.avif');
});

it('returns null when a candidate is missing a sibling, after attempting generation for every candidate', function (): void {
    $original = 'https://x/img-300x200.jpg 300w, https://x/img-768x512.jpg 768w';

    $calls = 0;
    $siblingFor = function (string $url) use (&$calls): ?string {
        $calls++;

        return str_contains($url, '300x200') ? 'https://x/img-300x200.avif' : null;
    };

    expect(AvifSrcsetBuilder::build($original, $siblingFor))->toBeNull()
        ->and($calls)->toBe(2);
});

it('returns null when no sibling exists', function (): void {
    expect(AvifSrcsetBuilder::build('https://x/a.jpg 1x', fn (): ?string => null))
        ->toBeNull();
});
