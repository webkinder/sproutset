<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use Webkinder\SproutsetPackage\Services\ImageOptimizer;

final class AdminNotificationManager
{
    public function initializeAdminNotifications(): void
    {
        add_action('admin_notices', $this->displayMediaSettingsNotice(...));
        add_action('admin_head-options-media.php', $this->disableMediaSettingsFields(...));

        if ($this->shouldShowOptimizationNotice()) {
            add_action('admin_notices', $this->displayOptimizationBinariesNotice(...));
        }
    }

    private function displayMediaSettingsNotice(): void
    {
        $currentScreen = get_current_screen();

        if (! $currentScreen || $currentScreen->id !== 'options-media') {
            return;
        }

        $this->renderMediaSettingsNotice();
    }

    private function renderMediaSettingsNotice(): void
    {
        echo '<div class="notice notice-info">';
        echo '<p><strong>Sproutset:</strong> ';
        echo esc_html__('Image size settings on this page are managed by Sproutset configuration and changes here will have no effect.', 'webkinder-sproutset');
        echo ' ';
        echo sprintf(
            // translators: %s is the path to the Sproutset configuration file
            esc_html__('Configure image sizes in %s.', 'webkinder-sproutset'),
            '<code>config/sproutset-config.php</code>'
        );
        echo '</p>';
        echo '</div>';
    }

    private function disableMediaSettingsFields(): void
    {
        ?>
        <style>
            input[name="thumbnail_size_w"],
            input[name="thumbnail_size_h"],
            input[name="thumbnail_crop"],
            input[name="medium_size_w"],
            input[name="medium_size_h"],
            input[name="large_size_w"],
            input[name="large_size_h"] {
                opacity: 0.6;
                cursor: not-allowed;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fieldNames = [
                    'thumbnail_size_w',
                    'thumbnail_size_h',
                    'thumbnail_crop',
                    'medium_size_w',
                    'medium_size_h',
                    'large_size_w',
                    'large_size_h'
                ];

                fieldNames.forEach(function(fieldName) {
                    const field = document.querySelector('input[name="' + fieldName + '"]');
                    if (field) {
                        field.setAttribute('readonly', 'readonly');
                        field.setAttribute('disabled', 'disabled');
                    }
                });
            });
        </script>
        <?php
    }

    private function shouldShowOptimizationNotice(): bool
    {
        if (! config('sproutset-config.auto_optimize_images', false)) {
            return false;
        }

        $environment = defined('WP_ENV') ? constant('WP_ENV') : 'production';

        return in_array($environment, ['development', 'staging'], true);
    }

    private function displayOptimizationBinariesNotice(): null
    {
        $missingBinaries = $this->getMissingOptimizationBinaries();

        if ($missingBinaries === []) {
            return null;
        }

        $this->renderOptimizationBinariesNotice($missingBinaries);

        return null;
    }

    private function getMissingOptimizationBinaries(): array
    {
        $optimizer = ImageOptimizer::getInstance();
        $binaries = ImageOptimizer::getAvailableOptimizers();
        $missingBinaries = [];

        foreach ($binaries as $binaryName => $config) {
            if (! $optimizer->checkBinaryAvailability($binaryName)) {
                $missingBinaries[] = $config['name'];
            }
        }

        return $missingBinaries;
    }

    private function renderOptimizationBinariesNotice(array $missingBinaries): void
    {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>Sproutset:</strong> ';
        echo esc_html__('Image optimization is enabled but some optimization packages are missing. Images may not be optimized.', 'webkinder-sproutset');
        echo '</p>';
        echo '<p>';
        echo esc_html__('Missing packages:', 'webkinder-sproutset');
        echo ' <code>'.esc_html(implode(', ', $missingBinaries)).'</code>';
        echo '</p>';
        echo '<p>';
        printf(
            // translators: %1$s is the opening tag for the link to the Spatie Image Optimizer documentation, %2$s is the closing tag for the link
            esc_html__('Install the missing packages to enable full image optimization. See the %1$sSpatie Image Optimizer documentation%2$s for installation instructions.', 'webkinder-sproutset'),
            '<a href="https://github.com/spatie/image-optimizer#optimization-tools" target="_blank" rel="noopener noreferrer">',
            '</a>'
        );
        echo '</p>';
        echo '</div>';
    }
}
