<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

final class AvifSrcsetBuilder
{
    /**
     * @param  callable(string): ?string  $siblingFor  Maps a candidate URL to its AVIF sibling URL, or null.
     */
    public static function build(string $originalSrcset, callable $siblingFor): ?string
    {
        $candidates = array_filter(array_map(trim(...), explode(',', $originalSrcset)));
        $out = [];
        $complete = true;

        foreach ($candidates as $candidate) {
            $parts = preg_split('/\s+/', $candidate, 2);

            if ($parts === false) {
                continue;
            }

            if ($parts[0] === '') {
                continue;
            }

            $url = $parts[0];
            $descriptor = $parts[1] ?? '';

            $sibling = $siblingFor($url);

            if ($sibling === null) {
                $complete = false;

                continue;
            }

            $out[] = $descriptor === '' ? $sibling : $sibling.' '.$descriptor;
        }

        return ($complete && $out !== []) ? implode(', ', $out) : null;
    }
}
