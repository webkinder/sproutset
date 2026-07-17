<?php

declare(strict_types=1);

namespace Webkinder\Sproutset\Images;

final class MediaSettingsLock
{
    /**
     * @var array<string, list<string>>
     */
    private const INPUT_MAP = [
        'thumbnail' => ['thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop'],
        'medium' => ['medium_size_w', 'medium_size_h'],
        'large' => ['large_size_w', 'large_size_h'],
    ];

    /**
     * @param  array<array-key, mixed>  $rawConfig
     * @return list<string>
     */
    public function lockedInputIds(array $rawConfig): array
    {
        $ids = [];

        foreach (self::INPUT_MAP as $sizeName => $inputIds) {
            if (isset($rawConfig[$sizeName]) && is_array($rawConfig[$sizeName])) {
                $ids = array_merge($ids, $inputIds);
            }
        }

        return $ids;
    }
}
