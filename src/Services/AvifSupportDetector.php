<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Services;

use Throwable;
use WP_Error;

final class AvifSupportDetector
{
    private const CACHE_KEY = 'sproutset_avif_output_supported';

    private const PROBE_SOURCE = __DIR__.'/../../resources/avif-probe.png';

    public function isAvifOutputSupported(): bool
    {
        $override = apply_filters('sproutset_avif_output_supported', null);

        if ($override !== null) {
            return (bool) $override;
        }

        $cached = get_transient(self::CACHE_KEY);

        if ($cached !== false) {
            return $cached === '1';
        }

        $supported = $this->probeAvifOutput();
        set_transient(self::CACHE_KEY, $supported ? '1' : '0', WEEK_IN_SECONDS);

        return $supported;
    }

    public static function clearCache(): void
    {
        delete_transient(self::CACHE_KEY);
    }

    private function probeAvifOutput(): bool
    {
        if (! file_exists(self::PROBE_SOURCE)) {
            return false;
        }

        $sourceCopy = tempnam(get_temp_dir(), 'sproutset-avif-probe');

        if ($sourceCopy === false) {
            return false;
        }

        $outputPath = $sourceCopy.'.avif';

        try {
            if (! copy(self::PROBE_SOURCE, $sourceCopy)) {
                return false;
            }

            $editor = wp_get_image_editor($sourceCopy);

            if ($editor instanceof WP_Error) {
                return false;
            }

            $result = $editor->save($outputPath, 'image/avif');

            return ! ($result instanceof WP_Error)
                && is_array($result)
                && isset($result['path'])
                && file_exists($result['path'])
                && filesize($result['path']) > 0;
        } catch (Throwable) {
            return false;
        } finally {
            if (file_exists($sourceCopy)) {
                @unlink($sourceCopy);
            }

            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
        }
    }
}
