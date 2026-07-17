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

    /*
    |--------------------------------------------------------------------------
    | AVIF Conversion
    |--------------------------------------------------------------------------
    |
    | Opt-in AVIF delivery for images rendered through <x-sproutset-image>.
    | When enabled and the server can actually write AVIF, an AVIF <source> is
    | layered over the original <img>. Disabled is a guaranteed no-op.
    |
    |   - enabled: master switch (false = identical to no AVIF at all)
    |   - quality: encode quality 0-100
    |
    */

    'avif' => [
        'enabled' => false,
        'quality' => 50,
    ],
];
