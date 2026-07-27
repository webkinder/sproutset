<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final class AvifSignature
{
    public static function isAvif(string $bytes): bool
    {
        if (strlen($bytes) < 12) {
            return false;
        }

        if (substr($bytes, 4, 4) !== 'ftyp') {
            return false;
        }

        $sizeBytes = substr($bytes, 0, 4);
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('N', $sizeBytes);
        $boxSize = $unpacked[1];

        if ($boxSize < 12 || $boxSize > strlen($bytes)) {
            $majorBrand = substr($bytes, 8, 4);

            return $majorBrand === 'avif' || $majorBrand === 'avis';
        }

        $brands = substr($bytes, 8, $boxSize - 8);

        return str_contains($brands, 'avif') || str_contains($brands, 'avis');
    }
}
