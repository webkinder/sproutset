<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | AVIF Conversion
    |--------------------------------------------------------------------------
    |
    | Automatically convert JPEG and PNG images to AVIF format on upload
    | or on request.
    |
    */

    'convert_to_avif' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto Optimize Images
    |--------------------------------------------------------------------------
    |
    | Automatically optimize images using available optimizer binaries
    | (jpegoptim, optipng, pngquant, cwebp, avifenc, etc.) on upload
    | or on request.
    |
    */

    'auto_optimize_images' => true,

    /*
    |--------------------------------------------------------------------------
    | Image Sizes
    |--------------------------------------------------------------------------
    |
    | Define all image sizes with their dimensions, crop behavior, and
    | responsive variants. Required sizes: thumbnail, medium, medium_large, large
    |
    | Available properties per size:
    |
    | - width: Image width in pixels
    | - height: Image height in pixels (0 for auto)
    | - crop: Hard crop (true) or proportional resize (false)
    | - srcset: Array of multipliers for responsive variants (e.g., [0.5, 2])
    | - show_in_ui: Show in WordPress media UI (true, false, or custom label string)
    | - post_types: Array of post types to limit this size to (e.g., ['post', 'page'])
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
            'srcset' => [
                0.5,
                2,
            ],
        ],

        'large' => [
            'width' => 1024,
            'height' => 1024,
            'crop' => false,
            'srcset' => [
                0.5,
                2,
            ],
            'show_in_ui' => true,
        ],
    ],

];
