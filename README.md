# Sproutset

A Composer package for handling responsive images in Roots Bedrock + Sage + Blade projects.

## Overview

Sproutset is a modern responsive image handling solution designed specifically for WordPress projects using the Roots stack (Bedrock + Sage + Blade). Inspired by [mindkomm/timmy](https://github.com/mindkomm/timmy) for Timber/Twig, Sproutset makes it easy to generate and output responsive `<img>` tags with srcset and sizes attributes in your Blade templates.

The package consists of:

- **Core Image Service**: Handles image size registration and responsive image generation
- **Blade Components**: Provides a easy-to-use component for rendering responsive images
- **Image Optimizer Integration**: Automatic image optimization via Spatie Image Optimizer

## Getting Started

### Tested With

- PHP ^8.2
- Roots Acorn ^5.0
- WordPress ^6.8

### Installation

Add Sproutset to your Bedrock project:

```bash
composer require webkinder/sproutset
```

The package will automatically register its service provider via Acorn. Configure your responsive image sizes in your theme's configuration files.

### Configuration

Publish the configuration file and edit it according to your needs:

```bash
wp acorn vendor:publish --tag=sproutset-config
```

### Register Image Sizes

Add custom image sizes in `config/sproutset-config.php` under the `image_sizes` array:

```php
'image_sizes' => [
    'my_custom_size' => [
        'width' => 800,           // Width in pixels (0 for proportional)
        'height' => 600,          // Height in pixels (0 for proportional)
        'crop' => true,           // true for hard crop, false for proportional resize
    ],
],
```

**Available Options:**

- **`width`** (int): Maximum width in pixels. Set to `0` for proportional scaling based on height.
- **`height`** (int): Maximum height in pixels. Set to `0` for proportional scaling based on width.
- **`crop`** (bool): `true` for hard crop to exact dimensions, `false` for proportional resize.
- **`srcset`** (array, optional): Generate additional sizes for high-DPI displays. Example: `[0.5, 2]` creates 0.5x and 2x variants.
- **`show_in_ui`** (bool|string, optional): `true` to show in WordPress media UI, or provide a custom label string.
- **`post_types`** (array, optional): Limit size generation to specific post types. Example: `['post', 'page']`. Default: not set (generates for all post types). Empty array `[]` prevents generation for all post types.

**Example with all options:**

```php
'hero_image' => [
    'width' => 1920,
    'height' => 1080,
    'crop' => true,
    'srcset' => [0.5, 2],              // Generates hero_image@0.5x and hero_image@2x
    'show_in_ui' => 'Hero Image',      // Shows in media library with custom label
    'post_types' => ['post', 'page'],  // Only generates sizes, if attachments were uploaded on posts or pages
],
```

## Usage

### Using the Image Component

Render responsive images in your Blade templates using the `<x-sproutset-image>` component:

```blade
<x-sproutset-image :id="$attachment_id" size="large" />
```

**Required Parameters:**

- **`id`** (int): WordPress attachment ID of the image.

**Optional Parameters:**

- **`size`** (string): Image size to use. Default: `'large'`. Must match a size defined in your config.
- **`sizes`** (string|null): Custom `sizes` attribute for responsive images. Default: auto-generated based on image width.
- **`alt`** (string|null): Alt text for the image. Default: uses WordPress attachment alt text.
- **`width`** (int|null): Custom width attribute. Default: auto-detected from image size.
- **`height`** (int|null): Custom height attribute. Default: auto-detected from image size.
- **`class`** (string|null): CSS classes to apply to the `<img>` tag.
- **`lazy`** (bool): Enable lazy loading. Default: `true`.
- **`decoding`** (string): Image decoding strategy. Default: `'async'`. Options: `'async'`, `'sync'`, `'auto'`.

**Examples:**

```blade
{{-- Basic usage --}}
<x-sproutset-image id="123" size="medium" />

{{-- With custom alt text and CSS class --}}
<x-sproutset-image
  :id="$post->thumbnail()->id"
  size="hero_image"
  alt="Hero banner"
  class="w-full h-auto"
/>

{{-- Custom sizes attribute for advanced responsive behavior --}}
<x-sproutset-image
  :id="$image_id"
  size="large"
  sizes="(max-width: 768px) 100vw, 50vw"
/>

{{-- Disable lazy loading for above-the-fold images --}}
<x-sproutset-image :id="$hero_image" size="hero_image" :lazy="false" />
```

## Contributing

We welcome contributions from the community. Whether you're fixing bugs, adding features, or improving documentation, your help is appreciated.

Please read our [Contributing Guide](CONTRIBUTING.md) for details on:

- How to report bugs and security vulnerabilities
- Development workflow and branch strategy
- Coding standards and style guidelines
- Pull request process
- First-time contributor resources

For questions or support, feel free to open an issue on GitHub.

## License

Sproutset is open-source software licensed under the [GNU General Public License v3.0](LICENSE.md).

This means you are free to use, modify, and distribute this software under the terms of the GPL-3.0 license. See the [LICENSE.md](LICENSE.md) file for the full license text.
