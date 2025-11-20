# Sproutset

Modern responsive image management for projects using the Roots Acorn framework.

## Features

- Automatic `srcset` and `sizes` generation
- AVIF conversion for JPEG/PNG images
- Image optimization via Spatie Image Optimizer
- Custom image sizes with responsive variants
- Blade component with clean syntax
- On-the-fly size generation
- CLI batch optimization

## Requirements

- PHP ^8.2
- Roots Acorn ^5.0
- WordPress ^5.9 || ^6.0

## Installation

```bash
composer require webkinder/sproutset
wp acorn vendor:publish --tag=sproutset-config
```

The package auto-registers via Acorn. Configure image sizes in `config/sproutset-config.php`.

## Configuration

Edit `config/sproutset-config.php`:

```php
return [
  'convert_to_avif' => true,
  'auto_optimize_images' => true,
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
```

### Global Options

- **`convert_to_avif`**: Enable automatic AVIF conversion for JPEG/PNG images
- **`auto_optimize_images`**: Optimize images on upload (requires optimization binaries)

### Image Size Options

- **`width`**: Width in pixels (0 for proportional)
- **`height`**: Height in pixels (0 for proportional)
- **`crop`**: Hard crop (`true`) or proportional resize (`false`)
- **`srcset`**: Array of multipliers for responsive variants, e.g., `[0.5, 2]`
- **`show_in_ui`**: Show in media library (`true` or custom label string)
- **`post_types`**: Limit generation on upload to specific post types, e.g., `['post', 'page']`. Note: Missing sizes are still generated on-the-fly when requested.

**Required sizes:** `thumbnail`, `medium`, `medium_large`, `large`

**Example:**

```php
'hero' => [
  'width' => 1920,
  'height' => 1080,
  'crop' => true,
  'srcset' => [0.5, 2],
  'show_in_ui' => 'Hero Image',
  'post_types' => ['post', 'page'],
],
```

## Usage

Basic usage:

```blade
<x-sproutset-image :id="$attachment_id" sizeName="large" />
```

### Parameters

**Required:**

- **`id`**: WordPress attachment ID

**Optional:**

- **`sizeName`**: Image size name (default: `'large'`)
- **`sizes`**: Custom `sizes` attribute (default: auto-generated)
- **`alt`**: Alt text (default: from WordPress metadata)
- **`width`** / **`height`**: Custom dimensions (default: auto-detected)
- **`class`**: For custom classes
- **`useLazyLoading`**: Enable lazy loading (default: `true`)
- **`decodingMode`**: Decoding strategy (default: `'async'`)

### Examples

```blade
{{-- Basic --}}
<x-sproutset-image :id="123" sizeName="medium" />

{{-- Custom alt and class --}}
<x-sproutset-image :id="$post->thumbnail()->id" sizeName="hero" alt="Hero banner" class="w-full" />

{{-- Custom sizes attribute --}}
<x-sproutset-image :id="$id" sizeName="large" sizes="(max-width: 768px) 100vw, 50vw" />

{{-- Disable lazy loading (above-the-fold images) --}}
<x-sproutset-image :id="$hero" sizeName="hero" :useLazyLoading="false" />
```

### Automatic Behavior

- Auto-generated `sizes` attribute based on actual image width
- Smart `srcset` variants from your config (e.g., `@0.5x`, `@2x`)
- `object-fit: cover` applied when images are smaller than configured dimensions
- On-the-fly generation of missing sizes

## Optimization

Sproutset integrates with [Spatie Image Optimizer](https://github.com/spatie/image-optimizer) for image optimization.

### Install Optimization Binaries

**Supported formats:** JPEG (jpegoptim), PNG (optipng/pngquant), WebP (cwebp), AVIF (avifenc), SVG (svgo), GIF (gifsicle)

See [Spatie Image Optimizer](https://github.com/spatie/image-optimizer?tab=readme-ov-file#optimization-tools) for installation instructions.

### Automatic Optimization

When `auto_optimize_images` is enabled:

- Images are optimized on upload
- Generated sizes are optimized on-the-fly
- Runs in background via WordPress cron
- Already optimized images are skipped

### CLI Batch Optimization

```bash
wp acorn sproutset:optimize           # Optimize unoptimized images
wp acorn sproutset:optimize --force   # Re-optimize all images
```

The command shows a progress bar and lists available/missing binaries.

## Contributing

Contributions are welcome! See [Contributing Guide](CONTRIBUTING.md) for details on reporting bugs, development workflow, and pull request process.

## License

Licensed under [GPL-3.0](LICENSE.md). Free to use, modify, and distribute under GPL-3.0 terms.
