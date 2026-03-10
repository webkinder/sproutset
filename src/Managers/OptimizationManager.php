<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use Webkinder\SproutsetPackage\Services\CronOptimizer;
use Webkinder\SproutsetPackage\Support\ImageEditDetector;

final class OptimizationManager
{
    public function initializeOptimizationFeatures(): void
    {
        $this->registerAvifConversionIfEnabled();
        $this->registerAutoOptimizeImagesIfEnabled();
        $this->registerStaleOptimizationCleanup();
        $this->registerOptimizationOnMetadataUpdate();
        $this->initializeCronOptimizerIfEnabled();
    }

    private function registerAvifConversionIfEnabled(): void
    {
        if (! config('sproutset-config.convert_to_avif', false)) {
            return;
        }

        add_filter('image_editor_output_format', $this->createAvifConversionFilter());
    }

    private function createAvifConversionFilter(): callable
    {
        return function (array $outputFormats): array {
            $outputFormats['image/jpeg'] = 'image/avif';
            $outputFormats['image/png'] = 'image/avif';

            return $outputFormats;
        };
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
