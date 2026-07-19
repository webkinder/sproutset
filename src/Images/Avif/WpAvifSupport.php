<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images\Avif;

use Closure;
use Throwable;

final class WpAvifSupport implements AvifSupport
{
    private const string TRANSIENT_KEY = 'sproutset_avif_writable_v1';

    private ?bool $memo = null;

    /**
     * @param  Closure(): ?string|null  $probe  Test seam: returns encoded AVIF bytes, or null.
     */
    public function __construct(private readonly ?Closure $probe = null) {}

    public function isSupported(): bool
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $cached = $this->cachedVerdict();

        if ($cached !== null) {
            return $this->memo = $cached;
        }

        $verdict = $this->computeVerdict();
        $this->storeVerdict($verdict);

        return $this->memo = $verdict;
    }

    private function computeVerdict(): bool
    {
        try {
            $bytes = ($this->probe ?? $this->defaultProbeBytes(...))();

            return $bytes !== null && AvifSignature::isAvif($bytes);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Encode a throwaway 1x1 image to AVIF through the same WP editor generation
     * uses, and return the resulting bytes. No admin-only functions; safe on any
     * request. Returns null when the environment cannot even attempt it.
     */
    private function defaultProbeBytes(): ?string
    {
        if (! function_exists('wp_get_image_editor')) {
            return null;
        }

        $source = $this->writeProbeSource();

        if ($source === null) {
            return null;
        }

        $dest = $source.'.avif';
        $savedPath = null;

        try {
            $editor = wp_get_image_editor($source);

            if (is_wp_error($editor)) {
                return null;
            }

            $saved = $editor->save($dest, 'image/avif');

            if (is_wp_error($saved)) {
                return null;
            }

            $savedPath = $saved['path'];

            return is_file($savedPath) ? (string) file_get_contents($savedPath) : null;
        } finally {
            @unlink($source);
            @unlink($dest);

            if (is_string($savedPath)) {
                @unlink($savedPath);
            }
        }
    }

    private function writeProbeSource(): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sps_avif_');

        if ($tmp === false) {
            return null;
        }

        // Truecolor (RGBA) rather than grayscale+alpha: some GD builds (e.g. 2.3.3)
        // reject a grayscale-alpha PNG in imagecreatefromstring(), which would make
        // the probe a false negative on servers that can in fact write AVIF.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mP4DwQACfsD/Wj6HMwAAAAASUVORK5CYII=', true);

        if ($png === false || file_put_contents($tmp, $png) === false) {
            @unlink($tmp);

            return null;
        }

        return $tmp;
    }

    private function cachedVerdict(): ?bool
    {
        if (! function_exists('get_transient')) {
            return null;
        }

        // A missing transient returns boolean false, indistinguishable from a
        // stored false verdict; only the explicit '1'/'0' sentinels count as a
        // cached verdict, so an absent transient re-runs the probe.
        return match (get_transient(self::TRANSIENT_KEY)) {
            '1' => true,
            '0' => false,
            default => null,
        };
    }

    private function storeVerdict(bool $verdict): void
    {
        if (function_exists('set_transient')) {
            // WEEK_IN_SECONDS is a WordPress runtime constant not present in the
            // vendored stubs; the literal keeps `composer types:check` clean.
            set_transient(self::TRANSIENT_KEY, $verdict ? '1' : '0', 604800);
        }
    }
}
