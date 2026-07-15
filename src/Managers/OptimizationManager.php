<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use Closure;
use Webkinder\SproutsetPackage\Services\AvifSupportDetector;
use Webkinder\SproutsetPackage\Services\CronOptimizer;
use Webkinder\SproutsetPackage\Support\ImageEditDetector;
use WP_Site_Icon;

final class OptimizationManager
{
    private ?Closure $avifFilter = null;

    public function initializeOptimizationFeatures(): void
    {
        $this->registerAvifConversionIfEnabled();
        $this->registerAutoOptimizeImagesIfEnabled();
        $this->registerStaleOptimizationCleanup();
        $this->registerOptimizationOnMetadataUpdate();
        $this->initializeCronOptimizerIfEnabled();
        $this->registerSiteIconPngRegenerationIfEnabled();
    }

    private function registerAvifConversionIfEnabled(): void
    {
        if (! config('sproutset-config.convert_to_avif', false)) {
            return;
        }

        if (! (new AvifSupportDetector())->isAvifOutputSupported()) {
            return;
        }

        $this->avifFilter = $this->createAvifConversionFilter();
        add_filter('image_editor_output_format', $this->avifFilter);
    }

    private function registerSiteIconPngRegenerationIfEnabled(): void
    {
        if (! config('sproutset-config.convert_to_avif', false)) {
            return;
        }

        add_action('update_option_site_icon', $this->createSiteIconUpdateHandler(), 20, 2);
        add_action('added_option', $this->createSiteIconAddedHandler(), 20, 2);
    }

    private function createSiteIconUpdateHandler(): callable
    {
        return function (mixed $oldValue, mixed $newValue): void {
            $attachmentId = (int) $newValue;

            if ($attachmentId > 0) {
                $this->regenerateSiteIconSizesAsPng($attachmentId);
            }
        };
    }

    private function createSiteIconAddedHandler(): callable
    {
        return function (string $optionName, mixed $value): void {
            if ($optionName !== 'site_icon') {
                return;
            }

            $attachmentId = (int) $value;

            if ($attachmentId > 0) {
                $this->regenerateSiteIconSizesAsPng($attachmentId);
            }
        };
    }

    private function regenerateSiteIconSizesAsPng(int $attachmentId): void
    {
        if (! $this->avifFilter instanceof Closure) {
            return;
        }

        $file = get_attached_file($attachmentId);

        if (! $file || ! file_exists($file)) {
            return;
        }

        $metadata = wp_get_attachment_metadata($attachmentId);

        if (! is_array($metadata)) {
            return;
        }

        $wpSiteIcon = new WP_Site_Icon();
        $iconSizes = apply_filters('site_icon_image_sizes', $wpSiteIcon->site_icon_sizes);

        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach (array_keys($metadata['sizes']) as $sizeName) {
                if (str_starts_with((string) $sizeName, 'site_icon-')) {
                    unset($metadata['sizes'][$sizeName]);
                }
            }
        }

        remove_filter('image_editor_output_format', $this->avifFilter);

        $forcePngFilter = static function (array $formats): array {
            $formats['image/avif'] = 'image/png';
            $formats['image/jpeg'] = 'image/png';

            return $formats;
        };

        add_filter('image_editor_output_format', $forcePngFilter);

        try {
            foreach ($iconSizes as $size) {
                if ($size >= $wpSiteIcon->min_size) {
                    continue;
                }

                $sizeInfo = image_make_intermediate_size($file, $size, $size, true);

                if (is_array($sizeInfo)) {
                    $metadata['sizes']['site_icon-'.$size] = $sizeInfo;
                }
            }

            wp_update_attachment_metadata($attachmentId, $metadata);
        } finally {
            remove_filter('image_editor_output_format', $forcePngFilter);
            add_filter('image_editor_output_format', $this->avifFilter);
        }
    }

    private function createAvifConversionFilter(): Closure
    {
        return function (array $outputFormats): array {
            if ($this->isSiteIconCropRequest()) {
                return $outputFormats;
            }

            $outputFormats['image/jpeg'] = 'image/avif';
            $outputFormats['image/png'] = 'image/avif';

            return $outputFormats;
        };
    }

    private function isSiteIconCropRequest(): bool
    {
        return wp_doing_ajax()
            && ($_POST['action'] ?? '') === 'crop-image'
            && ($_POST['context'] ?? '') === 'site-icon';
    }

    private function registerAutoOptimizeImagesIfEnabled(): void
    {
        if (! config('sproutset-config.auto_optimize_images', false)) {
            return;
        }

        add_filter('wp_generate_attachment_metadata', $this->createOptimizationScheduler(), 10, 2);
    }

    private function createOptimizationScheduler(): callable
    {
        return function (array $metadata, int $attachmentId): array {
            CronOptimizer::scheduleAttachmentOptimization($attachmentId);

            return $metadata;
        };
    }

    private function registerStaleOptimizationCleanup(): void
    {
        add_filter('wp_generate_attachment_metadata', $this->clearStaleOptimizationMarkers(), 8, 2);
    }

    private function clearStaleOptimizationMarkers(): callable
    {
        return function (array $metadata, int $attachmentId): array {
            if (ImageEditDetector::isEditedImage($metadata)) {
                return $this->clearAllOptimizationMarkers($metadata);
            }

            if (isset($metadata['original_image'])) {
                return $this->clearSizeOptimizationMarkers($metadata);
            }

            return $metadata;
        };
    }

    private function clearAllOptimizationMarkers(array $metadata): array
    {
        unset($metadata['sproutset_optimized']);

        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach (array_keys($metadata['sizes']) as $sizeName) {
                unset($metadata['sizes'][$sizeName]['sproutset_optimized']);
            }
        }

        return $metadata;
    }

    private function clearSizeOptimizationMarkers(array $metadata): array
    {
        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return $metadata;
        }

        foreach (array_keys($metadata['sizes']) as $sizeName) {
            unset($metadata['sizes'][$sizeName]['sproutset_optimized']);
        }

        return $metadata;
    }

    private function registerOptimizationOnMetadataUpdate(): void
    {
        if (! config('sproutset-config.auto_optimize_images', false)) {
            return;
        }

        add_filter('wp_update_attachment_metadata', $this->triggerOptimizationOnEdit(), 999, 2);
    }

    private function triggerOptimizationOnEdit(): callable
    {
        return function (array $metadata, int $attachmentId): array {
            if (! wp_attachment_is_image($attachmentId)) {
                return $metadata;
            }

            if ($this->hasUnoptimizedSizes($metadata)) {
                CronOptimizer::scheduleAttachmentOptimization($attachmentId);
            }

            return $metadata;
        };
    }

    private function hasUnoptimizedSizes(array $metadata): bool
    {
        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return false;
        }

        foreach ($metadata['sizes'] as $sizeData) {
            if (! isset($sizeData['sproutset_optimized'])) {
                return true;
            }
        }

        return false;
    }

    private function initializeCronOptimizerIfEnabled(): void
    {
        if (! config('sproutset-config.auto_optimize_images', false)) {
            return;
        }

        CronOptimizer::initializeHooks();
    }
}
