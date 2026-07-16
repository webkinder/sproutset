<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\ImageInputNormalizer;
use Webkinder\Sproutset\Images\ImageRequest;

it('normalizes loose attribute input', function (): void {
    $request = ImageInputNormalizer::normalize(
        attachmentId: '42',
        sizeName: '  hero  ',
        sizes: '',
        alt: '  A cat  ',
        width: '800',
        height: null,
        class: '',
        loading: 'EAGER',
        decoding: 'nonsense',
        useAutoSizes: 'false',
        focalPoint: '1',
        focalPointX: '25.5',
        focalPointY: null,
    );

    expect($request)->toBeInstanceOf(ImageRequest::class)
        ->and($request->attachmentId)->toBe(42)
        ->and($request->sizeName)->toBe('hero')
        ->and($request->sizes)->toBeNull()
        ->and($request->alt)->toBe('A cat')
        ->and($request->width)->toBe(800)
        ->and($request->height)->toBeNull()
        ->and($request->class)->toBeNull()
        ->and($request->loading)->toBe('eager')
        ->and($request->decoding)->toBe('async')
        ->and($request->useAutoSizes)->toBeFalse()
        ->and($request->focalPoint)->toBeTrue()
        ->and($request->focalPointX)->toBe(25.5)
        ->and($request->focalPointY)->toBeNull();
});

it('falls back to documented defaults for empty or invalid input', function (): void {
    $request = ImageInputNormalizer::normalize(attachmentId: null);

    expect($request->attachmentId)->toBe(0)
        ->and($request->sizeName)->toBe('large')
        ->and($request->loading)->toBe('lazy')
        ->and($request->decoding)->toBe('async')
        ->and($request->useAutoSizes)->toBeTrue()
        ->and($request->focalPoint)->toBeFalse();
});
