<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifSignature;

// Minimal ftyp box: size(4) + 'ftyp' + major brand(4) + minor(4) + compatible brands.
function ftypBytes(string $majorBrand, string $compatible = ''): string
{
    $body = 'ftyp'.$majorBrand."\x00\x00\x00\x00".$compatible;
    $size = pack('N', 4 + mb_strlen($body));

    return $size.$body;
}

it('accepts avif branded bytes and rejects others', function (): void {
    expect(AvifSignature::isAvif(ftypBytes('avif')))->toBeTrue()
        ->and(AvifSignature::isAvif(ftypBytes('avis')))->toBeTrue()
        ->and(AvifSignature::isAvif(ftypBytes('mif1', 'avif')))->toBeTrue()   // avif in compatible brands
        ->and(AvifSignature::isAvif(ftypBytes('mp42')))->toBeFalse()          // ftyp but no avif brand
        ->and(AvifSignature::isAvif("\x89PNG\r\n\x1a\n"))->toBeFalse()        // a PNG header
        ->and(AvifSignature::isAvif(''))->toBeFalse()
        ->and(AvifSignature::isAvif('avif'))->toBeFalse();                    // too short, no ftyp box
});

it('does not scan past a corrupted box size for a spoofed brand', function (): void {
    // Box-size field is absurdly large (invalid); major brand is mp42; a later
    // "avif" sequence must NOT be treated as a match.
    $bytes = pack('N', 0xFFFFFFFF).'ftyp'.'mp42'."\x00\x00\x00\x00".'xxxxavifxxxx';

    expect(AvifSignature::isAvif($bytes))->toBeFalse();
});

it('accepts a valid major brand even when the box size is corrupted', function (): void {
    $bytes = pack('N', 0xFFFFFFFF).'ftyp'.'avif'."\x00\x00\x00\x00";

    expect(AvifSignature::isAvif($bytes))->toBeTrue();
});
