<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

final class ImageEditDetector
{
    public static function isEditedImage(array $metadata): bool
    {
        if (! isset($metadata['file']) || ! isset($metadata['original_image'])) {
            return false;
        }

        $filename = pathinfo($metadata['file'], PATHINFO_FILENAME);

        return (bool) preg_match('/-e\d+$/', $filename);
    }
}
