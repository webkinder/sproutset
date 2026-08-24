<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final class AvifSiblingPath
{
    public static function for(string $file, int $attachmentId): ?string
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($extension === '') {
            return null;
        }

        return mb_substr($file, 0, -mb_strlen($extension)).$attachmentId.'.avif';
    }
}
