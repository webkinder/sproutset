<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final readonly class CoreImageSizeOptions
{
    /**
     * @var array<string, array{width: string, height: string, crop?: string}>
     */
    private const OPTION_MAP = [
        'thumbnail' => ['width' => 'thumbnail_size_w', 'height' => 'thumbnail_size_h', 'crop' => 'thumbnail_crop'],
        'medium' => ['width' => 'medium_size_w', 'height' => 'medium_size_h'],
        'medium_large' => ['width' => 'medium_large_size_w', 'height' => 'medium_large_size_h'],
        'large' => ['width' => 'large_size_w', 'height' => 'large_size_h'],
    ];

    public function __construct(private ImageSizeConfigNormalizer $normalizer) {}

    /**
     * @param  array<array-key, mixed>  $rawConfig
     * @return array<string, int>
     */
    public function overrides(array $rawConfig): array
    {
        $normalized = $this->normalizer->normalize($rawConfig);

        $overrides = [];

        foreach (self::OPTION_MAP as $sizeName => $options) {
            if (! isset($normalized[$sizeName])) {
                continue;
            }

            $size = $normalized[$sizeName];
            $overrides[$options['width']] = $size['width'];
            $overrides[$options['height']] = $size['height'];

            if (isset($options['crop'])) {
                $overrides[$options['crop']] = $size['crop'] ? 1 : 0;
            }
        }

        return $overrides;
    }
}
