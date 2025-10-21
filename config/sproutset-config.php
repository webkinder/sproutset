<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | General configuration
    |--------------------------------------------------------------------------
    |
    |
    */

    'convert_to_avif' => true,

    /*
    |--------------------------------------------------------------------------
    | Image Sizes configuration
    |--------------------------------------------------------------------------
    |
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
