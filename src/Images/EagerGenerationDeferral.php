<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final readonly class EagerGenerationDeferral
{
    public function __construct(private ImageSizeConfigNormalizer $normalizer) {}

    /**
     * @param  array<array-key, mixed>  $rawConfig
     * @return list<string>
     */
    public function deferrableSizes(array $rawConfig): array
    {
        $registered = array_keys($this->normalizer->normalize($rawConfig));

        return array_values(array_diff($registered, $this->baseSizeNames($rawConfig)));
    }

    /**
     * @param  array<array-key, mixed>  $sizes
     * @param  array<array-key, mixed>  $rawConfig
     * @return array<array-key, mixed>
     */
    public function apply(array $sizes, array $rawConfig): array
    {
        foreach ($this->deferrableSizes($rawConfig) as $sizeName) {
            unset($sizes[$sizeName]);
        }

        return $sizes;
    }

    /**
     * @param  array<array-key, mixed>  $rawConfig
     */
    public function register(bool $enabled, array $rawConfig): void
    {
        if (! $enabled) {
            return;
        }

        if ($this->deferrableSizes($rawConfig) === []) {
            return;
        }

        add_filter('intermediate_image_sizes_advanced', fn (mixed $sizes): mixed => is_array($sizes) ? $this->apply($sizes, $rawConfig) : $sizes);
    }

    /**
     * @param  array<array-key, mixed>  $rawConfig
     * @return list<string>
     */
    private function baseSizeNames(array $rawConfig): array
    {
        $baseNames = [];

        foreach ($rawConfig as $sizeName => $sizeConfig) {
            if (is_string($sizeName) && is_array($sizeConfig)) {
                $baseNames[] = $sizeName;
            }
        }

        return $baseNames;
    }
}
