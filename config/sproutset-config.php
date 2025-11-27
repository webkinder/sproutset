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
    | Focal Point Cropping & On-Demand Generation
    |--------------------------------------------------------------------------
    |
    | Controls if and how Sproutset crops images around a defined focal point.
    |
    | The `focal_point_cropping` option accepts these values:
    |
    |   - false / null
    |       Disable all focal‑point based recropping.
    |
    |   - true
    |       Enable focal cropping with the default strategy ('immediate').
    |
    |   - [] (empty array)
    |       Equivalent to `['strategy' => 'immediate']`.
    |
    |   - ['strategy' => 'immediate'|'cron', 'delay_seconds' => int>=0]
    |       Fine‑grained control over how and when recropping happens:
    |
    |       strategy:
    |         'immediate' – crop all hard‑cropped sizes immediately on upload, when generated on-the-fly and
    |                       when the focal point is changed in the media library.
    |         'cron'      – defer recropping of all sizes to a WP‑Cron job.
    |
    |       delay_seconds:
    |         Delay (in seconds) before scheduling the cron recrop when using
    |         the 'cron' strategy. Defaults to 30 seconds.
    |
    */

    'focal_point_cropping' => true,

    /*
    |--------------------------------------------------------------------------
    | Image Size Synchronization
    |--------------------------------------------------------------------------
    |
    | Controls how Sproutset synchronizes required WordPress image size
    | options (thumbnail, medium, medium_large, large) with the configuration
    | defined below. Available strategies:
    |
    | - request: Run on every request.
    | - admin_request: Run only on privileged requests (admin, AJAX, cron, CLI).
    | - cron: Schedule a WP-Cron job (see cron_interval below).
    | - manual: Only run via CLI or custom code.
    |
    | The strategy can also be overridden via the environment variable
    | or PHP constant `SPROUTSET_IMAGE_SIZE_SYNC_STRATEGY`.
    |
    */

    'image_size_sync' => [
        'strategy' => 'admin_request',
        'cron_interval' => 'daily',
    ],

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
