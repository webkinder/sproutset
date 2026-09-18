<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\OriginalSrcsetFallback;

$srcset = 'https://ex.test/t-262x332.jpg 262w, https://ex.test/t-524x664.jpg 524w';
$original = 'https://ex.test/t.jpg';

it('appends the original and forces cover when the top multiplier crop is source-limited', function () use ($srcset, $original): void {
    expect(OriginalSrcsetFallback::augment($srcset, $original, 700, 900, 262, 332, 786))
        ->toBe([
            'srcset' => $srcset.', '.$original.' 700w',
            'cover' => true,
        ]);
});

it('advertises the effective width for a wide-short original', function () use ($srcset, $original): void {
    expect(OriginalSrcsetFallback::augment($srcset, $original, 1200, 800, 262, 332, 786))
        ->toBe([
            'srcset' => $srcset.', '.$original.' 631w',
            'cover' => true,
        ]);
});

it('does not inject a pathological wide-short original that would not help', function () use ($srcset, $original): void {
    expect(OriginalSrcsetFallback::augment($srcset, $original, 3000, 400, 262, 332, 786))
        ->toBe(['srcset' => $srcset, 'cover' => false]);
});

it('does not inject when the top multiplier crop already exists', function () use ($original): void {
    $full = 'https://ex.test/t-262x332.jpg 262w, https://ex.test/t-786x996.jpg 786w';

    expect(OriginalSrcsetFallback::augment($full, $original, 900, 1200, 262, 332, 786))
        ->toBe(['srcset' => $full, 'cover' => false]);
});

it('does not inject when the original does not beat the largest crop', function () use ($srcset, $original): void {
    expect(OriginalSrcsetFallback::augment($srcset, $original, 500, 640, 262, 332, 786))
        ->toBe(['srcset' => $srcset, 'cover' => false]);
});

it('does not truncate a large valid original below a lifted retina ceiling', function () use ($original): void {
    $srcset = 'https://ex.test/hero-1000x1000.jpg 1000w, https://ex.test/hero-2000x2000.jpg 2000w';

    expect(OriginalSrcsetFallback::augment($srcset, $original, 2600, 2600, 1000, 1000, 3000))
        ->toBe([
            'srcset' => $srcset.', '.$original.' 2600w',
            'cover' => true,
        ]);
});

it('caps the injected descriptor at the top target', function () use ($srcset, $original): void {
    // E = min(900, round(900 * 262/332) = 710) = 710, capped to topTarget 600.
    expect(OriginalSrcsetFallback::augment($srcset, $original, 900, 900, 262, 332, 600))
        ->toBe([
            'srcset' => $srcset.', '.$original.' 600w',
            'cover' => true,
        ]);
});

it('ignores digits inside candidate urls when reading the largest descriptor', function () use ($original): void {
    // "banner-1200w.jpg" must not be read as a 1200w descriptor.
    $srcset = 'https://ex.test/banner-1200w-262x332.jpg 262w, https://ex.test/banner-1200w-524x664.jpg 524w';

    expect(OriginalSrcsetFallback::augment($srcset, $original, 700, 900, 262, 332, 786))
        ->toBe([
            'srcset' => $srcset.', '.$original.' 700w',
            'cover' => true,
        ]);
});

it('appends the original as the only candidate for an empty srcset', function () use ($original): void {
    expect(OriginalSrcsetFallback::augment('', $original, 700, 900, 262, 332, 786))
        ->toBe([
            'srcset' => $original.' 700w',
            'cover' => true,
        ]);
});

it('does not duplicate an original already present in the srcset', function () use ($original): void {
    $srcset = 'https://ex.test/t-262x332.jpg 262w, '.$original.' 700w';

    expect(OriginalSrcsetFallback::augment($srcset, $original, 700, 900, 262, 332, 786))
        ->toBe(['srcset' => $srcset, 'cover' => false]);
});

it('returns unchanged when a dimension is unknown', function () use ($srcset, $original): void {
    expect(OriginalSrcsetFallback::augment($srcset, $original, 0, 900, 262, 332, 786))
        ->toBe(['srcset' => $srcset, 'cover' => false]);
});
