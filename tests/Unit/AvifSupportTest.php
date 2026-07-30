<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifSignature;
use Webkinder\Sproutset\Images\Avif\WpAvifSupport;

function avifFixtureBytes(): string
{
    $body = 'ftypavif'."\x00\x00\x00\x00";

    return pack('N', 4 + mb_strlen($body)).$body;
}

it('produces a well-formed avif fixture', function (): void {
    expect(AvifSignature::isAvif(avifFixtureBytes()))->toBeTrue();
});

it('reports supported when the probe yields valid avif bytes', function (): void {
    $support = new WpAvifSupport(fn (): string => avifFixtureBytes());

    expect($support->isSupported())->toBeTrue();
});

it('reports unsupported when the probe yields no valid avif', function (): void {
    $support = new WpAvifSupport(fn (): string => 'not-avif-bytes');

    expect($support->isSupported())->toBeFalse();
});

it('reports unsupported when the probe returns null', function (): void {
    $support = new WpAvifSupport(fn (): ?string => null);

    expect($support->isSupported())->toBeFalse();
});

it('reports unsupported and does not throw when the probe throws', function (): void {
    $support = new WpAvifSupport(function (): ?string {
        throw new RuntimeException('encoder exploded');
    });

    expect($support->isSupported())->toBeFalse();
});

it('uses a truecolor png as its probe source so gd builds that reject grayscale+alpha can read it', function (): void {
    $support = new WpAvifSupport;
    $method = new ReflectionClass($support)->getMethod('writeProbeSource');

    $path = $method->invoke($support);
    expect($path)->toBeString();
    assert(is_string($path));

    $bytes = (string) file_get_contents($path);
    @unlink($path);

    // The PNG IHDR color-type byte sits at offset 25: 2 = truecolor, 6 = truecolor+alpha.
    // Grayscale types (0, 4) are what some GD builds reject in imagecreatefromstring().
    expect(in_array(ord($bytes[25]), [2, 6], true))->toBeTrue();
});
