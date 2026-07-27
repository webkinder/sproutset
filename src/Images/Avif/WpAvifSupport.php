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

        return match (get_transient(self::TRANSIENT_KEY)) {
            '1' => true,
            '0' => false,
            default => null,
        };
    }

    private function storeVerdict(bool $verdict): void
    {
        if (function_exists('set_transient')) {
            set_transient(self::TRANSIENT_KEY, $verdict ? '1' : '0', 604800);
        }
    }
}
