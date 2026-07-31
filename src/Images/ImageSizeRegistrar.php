<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final readonly class ImageSizeRegistrar
{
    public function __construct(private ImageSizeConfigNormalizer $normalizer) {}

    /**
     * @param  array<array-key, mixed>  $rawConfig
     */
    public function register(array $rawConfig): void
    {
        foreach (array_keys(wp_get_registered_image_subsizes()) as $sizeName) {
            remove_image_size((string) $sizeName);
        }

        foreach ($this->normalizer->normalize($rawConfig) as $sizeName => $size) {
            add_image_size($sizeName, $size['width'], $size['height'], $size['crop']);
        }
    }
}
