<?php

declare(strict_types=1);

return [
    // Configuration for Sproutset responsive image management.

    /*
    |--------------------------------------------------------------------------
    | Image Sizes
    |--------------------------------------------------------------------------
    |
    | The complete roster of image sizes Sproutset registers with WordPress.
    | On each request every configured size is registered via add_image_size();
    | sizes not listed here are not registered by Sproutset. Each size accepts:
    |
    |   - width:  target width in pixels
    |   - height: target height in pixels (0 = proportional)
    |   - crop:   true for a hard crop, false to scale within the box
    |   - srcset: optional list of multipliers; each adds an `@Nx` variant size
    |
    */

    'image_sizes' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'crop' => true,
        ],
        'medium' => [
            'width' => 400,
            'height' => 400,
            'crop' => false,
        ],
        'medium_large' => [
            'width' => 768,
            'height' => 0,
            'crop' => false,
            'srcset' => [0.5, 2],
        ],
        'large' => [
            'width' => 1024,
            'height' => 1024,
            'crop' => false,
            'srcset' => [0.5, 2],
        ],
    ],
];
