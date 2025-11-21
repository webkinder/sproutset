<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use Webkinder\SproutsetPackage\Support\ImageSizeConfigNormalizer;

final class ImageSizeManager
{
    public function initializeImageSizes(): void
    {
        add_action('after_setup_theme', $this->registerAllImageSizes(...), 10);
        add_action('after_setup_theme', $this->synchronizeImageSizeOptionsToDatabase(...), 11);
        add_filter('intermediate_image_sizes_advanced', $this->createPostTypeFilter(), 10, 3);
        add_filter('image_size_names_choose', $this->createUIFilter(), 10);
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

    private function synchronizeImageSizeOptionsToDatabase(): void
    {
        $imageSizes = ImageSizeConfigNormalizer::getAll();
        $configHash = md5(serialize($imageSizes));
        $storedHash = get_option('_sproutset_image_sizes_hash', '');

        if ($configHash === $storedHash) {
            return;
        }

        $this->updateDatabaseOptions($imageSizes);
        update_option('_sproutset_image_sizes_hash', $configHash);
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
}
