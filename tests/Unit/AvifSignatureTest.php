<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\Avif\AvifSignature;

// Minimal ftyp box: size(4) + 'ftyp' + major brand(4) + minor(4) + compatible brands.
function ftypBytes(string $majorBrand, string $compatible = ''): string
{
    $body = 'ftyp'.$majorBrand."\x00\x00\x00\x00".$compatible;
    $size = pack('N', 4 + strlen($body));

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
