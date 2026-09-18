<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class OriginalSrcsetFallback
{
    /**
     * @return array{srcset: string, cover: bool}
     */
    public static function augment(
        string $srcset,
        string $originalUrl,
        int $originalWidth,
        int $originalHeight,
        int $targetWidth,
        int $targetHeight,
        int $topTarget,
    ): array {
        $unchanged = ['srcset' => $srcset, 'cover' => false];

        if ($targetWidth <= 0 || $targetHeight <= 0 || $originalWidth <= 0 || $originalHeight <= 0) {
            return $unchanged;
        }

        $maxCandidate = self::largestDescriptor($srcset);

        if ($maxCandidate >= $topTarget) {
            return $unchanged;
        }

        $effectiveWidth = min($originalWidth, (int) round($originalHeight * $targetWidth / $targetHeight));

        if ($effectiveWidth <= $maxCandidate || self::alreadyPresent($srcset, $originalUrl)) {
            return $unchanged;
        }

        $candidate = $originalUrl.' '.min($effectiveWidth, $topTarget).'w';

        return [
            'srcset' => $srcset === '' ? $candidate : $srcset.', '.$candidate,
            'cover' => true,
        ];
    }

    private static function largestDescriptor(string $srcset): int
    {
        $largest = 0;

        foreach (explode(',', $srcset) as $candidate) {
            $tokens = preg_split('/\s+/', mb_trim($candidate));
            $descriptor = $tokens === false ? false : end($tokens);

            if (is_string($descriptor) && preg_match('/^(\d+)w$/', $descriptor, $matches) === 1) {
                $largest = max($largest, (int) $matches[1]);
            }
        }

        return $largest;
    }

    private static function alreadyPresent(string $srcset, string $originalUrl): bool
    {
        return array_any(explode(',', $srcset), fn ($entry): bool => str_starts_with(mb_trim($entry), $originalUrl.' ') || mb_trim($entry) === $originalUrl);
    }
}
