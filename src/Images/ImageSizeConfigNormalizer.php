<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class ImageSizeConfigNormalizer
{
    /**
     * @param  array<array-key, mixed>  $rawConfig
     * @return array<string, array{width: int, height: int, crop: bool}>
     */
    public function normalize(array $rawConfig): array
    {
        $normalized = [];

        foreach ($rawConfig as $sizeName => $sizeConfig) {
            if (! is_string($sizeName) || ! is_array($sizeConfig)) {
                continue;
            }

            $width = $this->toNonNegativeInt($sizeConfig['width'] ?? 0);
            $height = $this->toNonNegativeInt($sizeConfig['height'] ?? 0);
            $crop = (bool) ($sizeConfig['crop'] ?? false);

            $normalized[$sizeName] = ['width' => $width, 'height' => $height, 'crop' => $crop];

            foreach ($this->multipliers($sizeConfig['srcset'] ?? null) as $multiplier) {
                $normalized[$sizeName.'@'.$multiplier.'x'] = [
                    'width' => $width > 0 ? (int) ($width * $multiplier) : 0,
                    'height' => $height > 0 ? (int) ($height * $multiplier) : 0,
                    'crop' => $crop,
                ];
            }
        }

        return $normalized;
    }

    /**
     * @return list<float>
     */
    private function multipliers(mixed $srcset): array
    {
        if (! is_array($srcset)) {
            return [];
        }

        $multipliers = [];

        foreach ($srcset as $multiplier) {
            if (is_numeric($multiplier) && (float) $multiplier > 0.0) {
                $multipliers[] = (float) $multiplier;
            }
        }

        return $multipliers;
    }

    private function toNonNegativeInt(mixed $value): int
    {
        return is_numeric($value) ? max(0, (int) $value) : 0;
    }
}
