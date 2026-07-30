# Sproutset

Modern responsive image management for projects using the Roots Acorn framework.

## Requirements

- PHP ^8.4
- WordPress 6.5+
- Roots Acorn ^6.2

## Installation

Install via Composer:

```bash
composer require webkinder/sproutset
```

Acorn auto-discovers the service provider (declared under `extra.acorn.providers`), so no manual registration is needed. To customize the defaults, publish the config file:

```bash
wp acorn vendor:publish --tag=sproutset-config
```

This writes `config/sproutset.php`, read throughout via `config('sproutset.*')`.

## Configuration

`config/sproutset.php` has three blocks.

### Image sizes

`image_sizes` is the complete roster Sproutset registers with WordPress on every request. Each size takes:

- `width` — target width in pixels.
- `height` — target height in pixels (`0` = proportional).
- `crop` — `true` for a hard crop, `false` to scale within the box.
- `srcset` — optional list of multipliers; each adds an `@Nx` variant (e.g. `large@2x`).

The package ships `thumbnail`, `medium`, `medium_large`, and `large`. The four WordPress core sizes are driven from this config, and their fields are locked on **Settings → Media** so they can't drift.

```php
'image_sizes' => [
    'large' => [
        'width' => 1024,
        'height' => 1024,
        'crop' => false,
        'srcset' => [0.5, 2],
    ],
],
```

### AVIF

`avif` opts into AVIF delivery for images rendered through the component. Disabled is a guaranteed no-op.

```php
'avif' => [
    'enabled' => false, // opt-in
    'quality' => 50,    // 0–100
],
```

### Focal point

`focal_point` (default `true`) enables per-image focal points set in the Media Library. Set it to `false` to disable the picker, metadata honoring, and cropping.

```php
'focal_point' => true,
```

## Usage

Render an attachment with the `<x-sproutset-image>` Blade component:

```blade
<x-sproutset-image :attachment-id="$id" size-name="large" />
```

If the attachment can't be resolved, the component renders nothing.

### Attributes

| Attribute | Default | Notes |
| --- | --- | --- |
| `attachment-id` | `0` | Attachment ID (int or numeric string). |
| `size-name` | `large` | A registered size name. |
| `sizes` | `null` | Explicit `sizes` attribute; overrides auto sizes. |
| `alt` | `null` | Alt text. |
| `width` / `height` | `null` | Override the rendered box. |
| `class` | `null` | Merged with the element's classes. |
| `loading` | `lazy` | `lazy` or `eager`. |
| `decoding` | `async` | `async`, `sync`, or `auto`. |
| `use-auto-sizes` | `true` | Resolve `sizes="auto"` for lazy images when no explicit `sizes` is given. |
| `focal-point` | `false` | Enable per-call focal positioning. |
| `focal-point-x` / `focal-point-y` | `null` | Focal coordinates, `0`–`100`. |

Any other attribute (`id`, `data-*`, `aria-*`, `title`, …) passes through onto the `<img>`.

When `use-auto-sizes` is on and no explicit `sizes` is set, lazy-loaded images resolve `sizes="auto"`; eager images omit it (`sizes="auto"` is only valid for lazy images).

## AVIF

With `avif.enabled` set, Sproutset layers an AVIF `<source>` over the original `<img>` inside a `<picture>`:

```html
<picture>
  <source type="image/avif" srcset="…">
  <img src="…" …>
</picture>
```

It is additive — the original `<img>` is always the fallback — and never touches WordPress's global image pipeline, so favicons, `og:image`, and admin thumbnails are unaffected. The one CSS caveat: a direct-child selector like `.gallery > img` becomes `.gallery > picture > img`.

## Focal point

Set a focal point once per image in the Media Library and it is honored everywhere `<x-sproutset-image>` renders it — hard-crop sizes are re-cropped around the point, and cover contexts get `object-position`. A center (50/50) point is a no-op. Override a single placement with the component's attributes:

```blade
<x-sproutset-image :attachment-id="$id" size-name="large" focal-point focal-point-x="30" focal-point-y="70" />
```

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## License

Sproutset is open-source software licensed under the [GNU General Public License v3.0](LICENSE).
