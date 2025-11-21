<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use Webkinder\SproutsetPackage\Support\ImageSizeConfigNormalizer;
use Webkinder\SproutsetPackage\Support\SyncStrategy;

final class ImageSizeManager
{
    private const IMAGE_SIZE_HASH_OPTION = '_sproutset_image_sizes_hash';

    private const SYNC_CRON_HOOK = 'sproutset_sync_core_image_sizes';

    private const DEFAULT_SYNC_STRATEGY = SyncStrategy::ADMIN_REQUEST;

    public function initializeImageSizes(): void
    {
        add_action('after_setup_theme', $this->registerAllImageSizes(...), 10);
        $this->initializeSynchronizationStrategy();
        add_filter('intermediate_image_sizes_advanced', $this->createPostTypeFilter(), 10, 3);
        add_filter('image_size_names_choose', $this->createUIFilter(), 10);
    }

    public function synchronizeImageSizeOptionsToDatabase(bool $force = false): bool
    {
        $imageSizes = ImageSizeConfigNormalizer::getAll();
        $configHash = md5(serialize($imageSizes));
        $storedHash = get_option(self::IMAGE_SIZE_HASH_OPTION, '');

        if (! $force && $configHash === $storedHash) {
            return false;
        }

        $this->updateDatabaseOptions($imageSizes);
        update_option(self::IMAGE_SIZE_HASH_OPTION, $configHash);

        return true;
    }

    private function registerAllImageSizes(): void
    {
        $this->removeExistingImageSizes();
        $this->registerConfiguredImageSizes();
    }

    private function removeExistingImageSizes(): void
    {
        foreach (wp_get_registered_image_subsizes() as $sizeName => $sizeConfig) {
            remove_image_size($sizeName);
        }
    }

    private function registerConfiguredImageSizes(): void
    {
        $imageSizes = ImageSizeConfigNormalizer::getAll();

        foreach ($imageSizes as $sizeName => $sizeConfig) {
            $this->registerSingleImageSize($sizeName, $sizeConfig);
            $this->registerSrcsetVariantsForSize($sizeName, $sizeConfig);
        }
    }

    private function registerSingleImageSize(string $sizeName, array $sizeConfig): void
    {
        $width = $sizeConfig['width'];
        $height = $sizeConfig['height'];
        $crop = $sizeConfig['crop'];

        add_image_size($sizeName, $width, $height, $crop);
    }

    private function registerSrcsetVariantsForSize(string $sizeName, array $sizeConfig): void
    {
        if (! isset($sizeConfig['srcset']) || $sizeConfig['srcset'] === []) {
            return;
        }

        $width = $sizeConfig['width'];
        $height = $sizeConfig['height'];
        $crop = $sizeConfig['crop'];

        foreach ($sizeConfig['srcset'] as $multiplier) {
            $variantWidth = $width > 0 ? (int) ($width * $multiplier) : 0;
            $variantHeight = $height > 0 ? (int) ($height * $multiplier) : 0;
            $variantName = "{$sizeName}@{$multiplier}x";

            add_image_size($variantName, $variantWidth, $variantHeight, $crop);
        }
    }

    private function updateDatabaseOptions(array $imageSizes): void
    {
        $optionMapping = $this->getOptionMapping();

        foreach ($optionMapping as $sizeName => $options) {
            if (! isset($imageSizes[$sizeName])) {
                continue;
            }

            $this->updateSizeOptions($imageSizes[$sizeName], $options);
        }
    }

    private function getOptionMapping(): array
    {
        return [
            'thumbnail' => [
                'width' => 'thumbnail_size_w',
                'height' => 'thumbnail_size_h',
                'crop' => 'thumbnail_crop',
            ],
            'medium' => [
                'width' => 'medium_size_w',
                'height' => 'medium_size_h',
            ],
            'medium_large' => [
                'width' => 'medium_large_size_w',
                'height' => 'medium_large_size_h',
            ],
            'large' => [
                'width' => 'large_size_w',
                'height' => 'large_size_h',
            ],
        ];
    }

    private function updateSizeOptions(array $sizeConfig, array $options): void
    {
        if (isset($options['width'])) {
            $this->updateOptionIfChanged($options['width'], $sizeConfig['width']);
        }

        if (isset($options['height'])) {
            $this->updateOptionIfChanged($options['height'], $sizeConfig['height']);
        }

        if (isset($options['crop'])) {
            $this->updateOptionIfChanged($options['crop'], $sizeConfig['crop'] ? 1 : 0);
        }
    }

    private function updateOptionIfChanged(string $optionName, int $newValue): void
    {
        $currentValue = (int) get_option($optionName);

        if ($currentValue !== $newValue) {
            update_option($optionName, $newValue);
        }
    }

    private function createPostTypeFilter(): callable
    {
        return function (array $sizes, array $metadata, int $attachmentId): array {
            $postType = $this->getAttachmentPostType($attachmentId);

            return $this->filterSizesByPostType($sizes, $postType);
        };
    }

    private function getAttachmentPostType(int $attachmentId): ?string
    {
        $attachment = get_post($attachmentId);

        if (! $attachment || ! $attachment->post_parent) {
            return null;
        }

        $parentPost = get_post($attachment->post_parent);

        return $parentPost?->post_type;
    }

    private function filterSizesByPostType(array $sizes, ?string $postType): array
    {
        $imageSizes = ImageSizeConfigNormalizer::getAll();
        $filteredSizes = [];

        foreach ($sizes as $sizeName => $sizeData) {
            if ($this->shouldIncludeSize($sizeName, $postType, $imageSizes)) {
                $filteredSizes[$sizeName] = $sizeData;
            }
        }

        return $filteredSizes;
    }

    private function shouldIncludeSize(string $sizeName, ?string $postType, array $imageSizes): bool
    {
        $baseSizeName = $this->extractBaseSizeName($sizeName);

        if (! isset($imageSizes[$baseSizeName])) {
            return true;
        }

        $sizeConfig = $imageSizes[$baseSizeName];

        if (isset($sizeConfig['show_in_ui']) && $sizeConfig['show_in_ui'] !== false) {
            return true;
        }

        if (! array_key_exists('post_types', $sizeConfig)) {
            return true;
        }

        if (empty($sizeConfig['post_types'])) {
            return false;
        }

        return $postType !== null && in_array($postType, $sizeConfig['post_types'], true);
    }

    private function extractBaseSizeName(string $sizeName): string
    {
        return preg_replace('/@[\d.]+x$/', '', $sizeName) ?? $sizeName;
    }

    private function createUIFilter(): callable
    {
        return function (array $sizes): array {
            $imageSizes = ImageSizeConfigNormalizer::getAll();

            $filteredSizes = $sizes;

            foreach ($imageSizes as $sizeName => $sizeConfig) {
                if (! isset($sizeConfig['show_in_ui'])) {
                    continue;
                }

                $showInUi = $sizeConfig['show_in_ui'];

                if ($showInUi === true) {
                    if (! isset($filteredSizes[$sizeName])) {
                        $filteredSizes[$sizeName] = $this->generateSizeLabel($sizeName);
                    }
                } elseif (is_string($showInUi)) {
                    $filteredSizes[$sizeName] = $showInUi;
                }
            }

            return $filteredSizes;
        };
    }

    private function generateSizeLabel(string $sizeName): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $sizeName));
    }

    private function initializeSynchronizationStrategy(): void
    {
        $strategy = $this->determineSyncStrategy();

        if ($strategy === SyncStrategy::REQUEST) {
            add_action('after_setup_theme', $this->synchronizeImageSizeOptionsToDatabase(...), 11);
            $this->clearScheduledSyncEvent();

            return;
        }

        if ($strategy === SyncStrategy::ADMIN_REQUEST) {
            if ($this->isNonFrontendExecutionContext()) {
                add_action('after_setup_theme', $this->synchronizeImageSizeOptionsToDatabase(...), 11);
            }

            $this->clearScheduledSyncEvent();

            return;
        }

        if ($strategy === SyncStrategy::CRON) {
            add_action('init', $this->registerCronSynchronization(...), 10, 0);

            return;
        }

        $this->clearScheduledSyncEvent();
    }

    private function determineSyncStrategy(): SyncStrategy
    {
        $configStrategy = SyncStrategy::from($this->getImageSizeSyncConfig()['strategy'] ?? null);
        $strategy = $configStrategy ?? self::DEFAULT_SYNC_STRATEGY;

        if (defined('SPROUTSET_IMAGE_SIZE_SYNC_STRATEGY')) {
            $strategy = SyncStrategy::from(constant('SPROUTSET_IMAGE_SIZE_SYNC_STRATEGY')) ?? $strategy;
        }

        $envStrategy = getenv('SPROUTSET_IMAGE_SIZE_SYNC_STRATEGY');
        if (is_string($envStrategy) && $envStrategy !== '') {
            $strategy = SyncStrategy::from($envStrategy) ?? $strategy;
        }

        $filteredStrategy = SyncStrategy::from(
            apply_filters('sproutset_image_size_sync_strategy', $strategy->value)
        );

        return $filteredStrategy ?? $strategy;
    }

    private function isNonFrontendExecutionContext(): bool
    {
        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return true;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return true;
        }

        return is_admin();
    }

    private function registerCronSynchronization(): void
    {
        add_action(self::SYNC_CRON_HOOK, $this->synchronizeImageSizeOptionsToDatabase(...));

        if (! function_exists('wp_next_scheduled') || ! function_exists('wp_schedule_event')) {
            return;
        }

        $interval = $this->getCronInterval();
        if ($interval === null) {
            return;
        }

        if (! wp_next_scheduled(self::SYNC_CRON_HOOK)) {
            $firstRun = time() + (defined('MINUTE_IN_SECONDS') ? MINUTE_IN_SECONDS : 60);
            wp_schedule_event($firstRun, $interval, self::SYNC_CRON_HOOK);
        }
    }

    private function clearScheduledSyncEvent(): void
    {
        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook(self::SYNC_CRON_HOOK);
        }
    }

    private function getCronInterval(): string
    {
        $interval = $this->getImageSizeSyncConfig()['cron_interval'] ?? 'daily';
        $interval = is_string($interval) ? mb_strtolower($interval) : 'daily';

        if (! function_exists('wp_get_schedules')) {
            return $interval;
        }

        $schedules = wp_get_schedules();

        return isset($schedules[$interval]) ? $interval : 'daily';
    }

    private function getImageSizeSyncConfig(): array
    {
        $config = config('sproutset-config.image_size_sync');

        return is_array($config) ? $config : [];
    }
}
