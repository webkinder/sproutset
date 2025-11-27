<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use Webkinder\SproutsetPackage\Services\CronOptimizer;

final class OptimizationManager
{
    public function initializeOptimizationFeatures(): void
    {
        $this->registerAvifConversionIfEnabled();
        $this->registerAutoOptimizeImagesIfEnabled();
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

    private function initializeCronOptimizerIfEnabled(): void
    {
        if (! config('sproutset-config.auto_optimize_images', false)) {
            return;
        }

        CronOptimizer::initializeHooks();
    }
}
