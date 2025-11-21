<?php

declare(strict_types=1);

namespace Webkinder\SproutsetPackage\Support;

final class ImageSizeConfigNormalizer
{
    private static ?array $cache = null;

    public static function getAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rawConfig = config('sproutset-config.image_sizes', []);

        if (! is_array($rawConfig)) {
            return self::$cache = [];
        }

        $normalized = [];

        foreach ($rawConfig as $sizeName => $sizeConfig) {
            if (! is_string($sizeName)) {
                continue;
            }
            if (! is_array($sizeConfig)) {
                continue;
            }
            $normalizedSize = self::normalizeSizeConfiguration($sizeConfig);

            if ($normalizedSize === []) {
                continue;
            }

            $normalized[$sizeName] = $normalizedSize;
        }

        return self::$cache = $normalized;
    }

    public static function get(string $sizeName): array
    {
        $all = self::getAll();

        return $all[$sizeName] ?? [];
    }

    private static function normalizeSizeConfiguration(array $sizeConfig): array
    {
        $normalized = [
            'width' => max(0, (int) ($sizeConfig['width'] ?? 0)),
            'height' => max(0, (int) ($sizeConfig['height'] ?? 0)),
            'crop' => (bool) ($sizeConfig['crop'] ?? false),
        ];

        if (isset($sizeConfig['srcset']) && is_array($sizeConfig['srcset'])) {
            $normalizedSrcset = array_values(array_filter(
                array_map(
                    static fn ($multiplier): ?float => is_numeric($multiplier) ? (float) $multiplier : null,
                    $sizeConfig['srcset']
                ),
                static fn (?float $multiplier): bool => $multiplier !== null && $multiplier > 0.0
            ));

            if ($normalizedSrcset !== []) {
                $normalized['srcset'] = $normalizedSrcset;
            }
        }

        if (array_key_exists('show_in_ui', $sizeConfig)) {
            $showInUi = $sizeConfig['show_in_ui'];

            if ($showInUi === true) {
                $normalized['show_in_ui'] = true;
            } elseif (is_string($showInUi)) {
                $trimmed = trim($showInUi);

                if ($trimmed !== '') {
                    $normalized['show_in_ui'] = $trimmed;
                }
            }
        }

        if (array_key_exists('post_types', $sizeConfig) && is_array($sizeConfig['post_types'])) {
            $normalizedPostTypes = array_values(array_filter(
                array_map(
                    static fn ($postType): string => is_string($postType) ? trim($postType) : '',
                    $sizeConfig['post_types']
                ),
                static fn (string $postType): bool => $postType !== ''
            ));

            if ($normalizedPostTypes !== []) {
                $normalized['post_types'] = $normalizedPostTypes;
            } elseif ($sizeConfig['post_types'] === []) {
                $normalized['post_types'] = [];
            }
        } elseif (array_key_exists('post_types', $sizeConfig) && $sizeConfig['post_types'] === []) {
            $normalized['post_types'] = [];
        }

        return $normalized;
    }
}
