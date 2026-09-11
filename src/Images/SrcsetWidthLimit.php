<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final readonly class SrcsetWidthLimit
{
    private const int WP_DEFAULT_MAX_SRCSET_WIDTH = 2048;

    public function __construct(private ImageSizeConfigNormalizer $normalizer) {}

    /**
     * @param  array<array-key, mixed>  $rawConfig
     */
    public function ceilingFor(array $rawConfig): ?int
    {
        $widths = array_column($this->normalizer->normalize($rawConfig), 'width');
        $largest = $widths === [] ? 0 : max($widths);

        return $largest > self::WP_DEFAULT_MAX_SRCSET_WIDTH ? $largest : null;
    }

    /**
     * @param  array<array-key, mixed>  $rawConfig
     */
    public function register(array $rawConfig): void
    {
        $ceiling = $this->ceilingFor($rawConfig);

        if ($ceiling === null) {
            return;
        }

        add_filter('max_srcset_image_width', static function (mixed $currentMax) use ($ceiling): int {
            $current = is_numeric($currentMax) ? (int) $currentMax : 0;

            return max($current, $ceiling);
        });
    }
}
