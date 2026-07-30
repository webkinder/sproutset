<?php

declare(strict_types=1);

use Webkinder\Sproutset\Images\MediaSettingsLock;

it('lists the locked input ids and excludes medium_large', function (): void {
    $ids = (new MediaSettingsLock)->lockedInputIds([
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        'medium_large' => ['width' => 768, 'height' => 0, 'crop' => false],
        'large' => ['width' => 1024, 'height' => 1024, 'crop' => false],
    ]);

    expect($ids)->toBe([
        'thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop',
        'medium_size_w', 'medium_size_h',
        'large_size_w', 'large_size_h',
    ]);
});

it('locks only the sizes present in config', function (): void {
    $ids = (new MediaSettingsLock)->lockedInputIds([
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
    ]);

    expect($ids)->toBe(['thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop']);
});
