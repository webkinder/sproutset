<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage;

final readonly class Sproutset
{
    public function __construct()
    {
        $this->registerImageSizes();
        $this->filterImageSizesByPostType();
        $this->filterImageSizesInUI();
    }

    private function registerImageSizes(): void
    {
        add_action('after_setup_theme', function (): void {
            $this->removeAllImageSizes();
            $this->registerConfiguredImageSizes();
        });
    }

    private function removeAllImageSizes(): void
    {
        foreach (wp_get_registered_image_subsizes() as $name => $config) {
            remove_image_size($name);
        }
    }

    private function registerConfiguredImageSizes(): void
    {
        $sizes = config('sproutset-image-sizes', []);

        foreach ($sizes as $name => $config) {
            $this->registerImageSize($name, $config);
            $this->registerSrcsetVariants($name, $config);
        }
    }

    private function registerImageSize(string $name, array $config): void
    {
        $width = $config['width'] ?? 0;
        $height = $config['height'] ?? 0;
        $crop = $config['crop'] ?? false;

        add_image_size($name, $width, $height, $crop);
    }

    private function registerSrcsetVariants(string $name, array $config): void
    {
        if (! isset($config['srcset']) || ! is_array($config['srcset'])) {
            return;
        }

        $width = $config['width'] ?? 0;
        $height = $config['height'] ?? 0;
        $crop = $config['crop'] ?? false;

        foreach ($config['srcset'] as $multiplier) {
            $multipliedWidth = $width > 0 ? (int) ($width * $multiplier) : 0;
            $multipliedHeight = $height > 0 ? (int) ($height * $multiplier) : 0;
            $srcsetName = "{$name}@{$multiplier}x";

            add_image_size($srcsetName, $multipliedWidth, $multipliedHeight, $crop);
        }
    }

    private function filterImageSizesByPostType(): void
    {
        add_filter('intermediate_image_sizes_advanced', function (array $sizes, array $metadata, int $attachmentId): array {
            $postType = $this->getAttachmentPostType($attachmentId);

            return $this->filterSizesByAllowedPostTypes($sizes, $postType);
        }, 10, 3);
    }

    private function getAttachmentPostType(int $attachmentId): ?string
    {
        $post = get_post($attachmentId);

        if (! $post || ! $post->post_parent) {
            return null;
        }

        $parent = get_post($post->post_parent);

        return $parent ? $parent->post_type : null;
    }

    private function filterSizesByAllowedPostTypes(array $sizes, ?string $postType): array
    {
        $config = config('sproutset-image-sizes', []);
        $filteredSizes = [];

        foreach ($sizes as $sizeName => $sizeData) {
            $baseSizeName = $this->getBaseSizeName($sizeName);

            if (! isset($config[$baseSizeName])) {
                $filteredSizes[$sizeName] = $sizeData;

                continue;
            }

            $sizeConfig = $config[$baseSizeName];

            if (isset($sizeConfig['show_in_ui']) && $sizeConfig['show_in_ui'] !== false) {
                $filteredSizes[$sizeName] = $sizeData;

                continue;
            }

            if (! isset($sizeConfig['post_types'])) {
                $filteredSizes[$sizeName] = $sizeData;

                continue;
            }

            if (! is_array($sizeConfig['post_types'])) {
                continue;
            }

            if (empty($sizeConfig['post_types'])) {
                continue;
            }

            if ($postType !== null && in_array($postType, $sizeConfig['post_types'], true)) {
                $filteredSizes[$sizeName] = $sizeData;
            }
        }

        return $filteredSizes;
    }

    private function getBaseSizeName(string $sizeName): string
    {
        return preg_replace('/@[\d.]+x$/', '', $sizeName) ?? $sizeName;
    }

    private function filterImageSizesInUI(): void
    {
        add_filter('image_size_names_choose', function (array $sizes): array {
            $config = config('sproutset-image-sizes', []);
            $filteredSizes = [];

            foreach ($config as $sizeName => $sizeConfig) {
                if (isset($sizeConfig['show_in_ui'])) {
                    $showInUi = $sizeConfig['show_in_ui'];

                    if ($showInUi === true) {
                        $filteredSizes[$sizeName] = $sizes[$sizeName] ?? ucwords(str_replace(['-', '_'], ' ', $sizeName));
                    } elseif (is_string($showInUi)) {
                        $filteredSizes[$sizeName] = $showInUi;
                    }
                }
            }

            if (isset($sizes['full'])) {
                $filteredSizes['full'] = $sizes['full'];
            }

            return $filteredSizes;
        });
    }
}
