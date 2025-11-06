<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Managers;

use RuntimeException;

final class ConfigurationValidator
{
    private const REQUIRED_SIZES = ['thumbnail', 'medium', 'medium_large', 'large'];

    public function validateRequiredImageSizes(): void
    {
        $configuredSizes = config('sproutset-config.image_sizes', []);
        $missingSizes = $this->findMissingSizes($configuredSizes);

        if ($missingSizes === []) {
            return;
        }

        $this->handleMissingSizes($missingSizes);
    }

    private function findMissingSizes(array $configuredSizes): array
    {
        return array_filter(
            self::REQUIRED_SIZES,
            fn (string $size): bool => ! isset($configuredSizes[$size])
        );
    }

    private function handleMissingSizes(array $missingSizes): never
    {
        $errorMessage = sprintf(
            // translators: %s is a comma-separated list of missing image sizes
            __('Sproutset: Required image sizes are missing in config/sproutset-config.php: %s', 'webkinder-sproutset'),
            implode(', ', $missingSizes)
        );

        if (function_exists('wp_die')) {
            wp_die(
                esc_html($errorMessage),
                __('Sproutset Configuration Error', 'webkinder-sproutset'),
                ['response' => 500]
            );
        }

        throw new RuntimeException($errorMessage);
    }
}
