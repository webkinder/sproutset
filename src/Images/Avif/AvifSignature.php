<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final class AvifSignature
{
    /**
     * True when the bytes are an ISO-BMFF stream whose ftyp box declares an
     * avif/avis brand — the only reliable proof the encoder actually wrote AVIF.
     */
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
        $boxSize = ($boxSize < 12 || $boxSize > strlen($bytes)) ? strlen($bytes) : $boxSize;

        // Major brand (bytes 8-12) plus the compatible-brands list fill the ftyp box.
        $brands = substr($bytes, 8, $boxSize - 8);

        return str_contains($brands, 'avif') || str_contains($brands, 'avis');
    }
}
