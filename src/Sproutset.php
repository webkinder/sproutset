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
        $this->addMediaSettingsNotice();
        $this->registerAvifConversion();
        $this->registerImageOptimization();
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
        $imageSizes = config('sproutset-config.image_sizes', []);

        foreach ($imageSizes as $name => $sizeConfig) {
            $this->registerImageSize($name, $sizeConfig);
            $this->registerSrcsetVariants($name, $sizeConfig);
        }
    }

    private function registerImageSize(string $name, array $sizeConfig): void
    {
        $width = $sizeConfig['width'] ?? 0;
        $height = $sizeConfig['height'] ?? 0;
        $crop = $sizeConfig['crop'] ?? false;

        add_image_size($name, $width, $height, $crop);
    }

    private function registerSrcsetVariants(string $name, array $sizeConfig): void
    {
        if (! isset($sizeConfig['srcset']) || ! is_array($sizeConfig['srcset'])) {
            return;
        }

        $width = $sizeConfig['width'] ?? 0;
        $height = $sizeConfig['height'] ?? 0;
        $crop = $sizeConfig['crop'] ?? false;

        foreach ($sizeConfig['srcset'] as $multiplier) {
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
        $imageSizes = config('sproutset-config.image_sizes', []);
        $filteredSizes = [];

        foreach ($sizes as $sizeName => $sizeData) {
            $baseSizeName = $this->getBaseSizeName($sizeName);

            if (! isset($imageSizes[$baseSizeName])) {
                $filteredSizes[$sizeName] = $sizeData;

                continue;
            }

            $sizeConfig = $imageSizes[$baseSizeName];

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
            $imageSizes = config('sproutset-config.image_sizes', []);
            $filteredSizes = [];

            foreach ($imageSizes as $sizeName => $sizeConfig) {
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

    private function addMediaSettingsNotice(): void
    {
        add_action('admin_notices', function (): void {
            $screen = get_current_screen();

            if (! $screen || $screen->id !== 'options-media') {
                return;
            }

            echo '<div class="notice notice-info">';
            echo '<p><strong>Sproutset:</strong> ';
            echo esc_html__('Image size settings on this page are managed by Sproutset configuration and changes here will have no effect.', 'sproutset');
            echo ' ';
            echo sprintf(
                esc_html__('Configure image sizes in %s.', 'sproutset'),
                '<code>config/sproutset-config.php</code>'
            );
            echo '</p>';
            echo '</div>';
        });
    }

    private function registerAvifConversion(): void
    {
        if (! config('sproutset-config.convert_to_avif', false)) {
            return;
        }

        add_filter('image_editor_output_format', function (array $output_format): array {
            $output_format['image/jpeg'] = 'image/avif';
            $output_format['image/png'] = 'image/avif';

            return $output_format;
        });
    }

    private function registerImageOptimization(): void
    {
        add_filter('wp_generate_attachment_metadata', function (array $metadata, int $attachmentId): array {
            $optimizer = Services\ImageOptimizer::getInstance();

            return $optimizer->optimizeAttachmentSizes($attachmentId, $metadata);
        }, 10, 2);
    }
}
