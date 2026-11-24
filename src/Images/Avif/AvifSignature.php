<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final class AvifSignature
{
    public static function isAvif(string $bytes): bool
    {
        if (mb_strlen($bytes, '8bit') < 12) {
            return false;
        }

        if (mb_substr($bytes, 4, 4, '8bit') !== 'ftyp') {
            return false;
        }

        $sizeBytes = mb_substr($bytes, 0, 4, '8bit');
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', $sizeBytes);
        $boxSize = $unpacked[1];

        if ($boxSize < 12 || $boxSize > mb_strlen($bytes, '8bit')) {
            $majorBrand = mb_substr($bytes, 8, 4, '8bit');

            return $majorBrand === 'avif' || $majorBrand === 'avis';
        }

        $brands = mb_substr($bytes, 8, $boxSize - 8, '8bit');

        return str_contains($brands, 'avif') || str_contains($brands, 'avis');
    }
}
