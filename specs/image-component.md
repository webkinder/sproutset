# Image component

The public face of Sproutset: the `<x-sproutset-image>` Blade component. It is a thin
presentation shell that turns a resolved image view-model into an `<img>` tag. It owns
the public API surface (attribute names, loose-type acceptance, attribute-bag
pass-through, class merge) and nothing else — all WordPress resolution lives behind the
`ImageResolver` seam and is added in later steps.

## Behavior

Consumers render `<x-sproutset-image :attachment-id="$id" size-name="large" />`. The
component accepts the same thirteen attributes as the previous package version, with the
same defaults, and normalizes loose input (HTML-attribute strings, ints, bools, `null`)
through `ImageInputNormalizer` into a single `ImageRequest` value object:

| Attribute | Default | Notes |
| --- | --- | --- |
| `attachment-id` | `0` | int / numeric string coerced to int |
| `size-name` | `large` | |
| `sizes` | `null` | |
| `alt` | `null` | |
| `width` / `height` | `null` | |
| `class` | `null` | merged with consumer classes |
| `loading` | `lazy` | enum: `lazy`, `eager` |
| `decoding` | `async` | enum: `async`, `sync`, `auto` |
| `use-auto-sizes` | `true` | |
| `focal-point` | `false` | |
| `focal-point-x` / `focal-point-y` | `null` | float 0–100 |

The component asks the container-bound `ImageResolver` to `resolve(ImageRequest): ?ResolvedImage`.
`ResolvedImage` carries only what rendering needs — `src`, `srcset`, `sizes`, `width`,
`height`, `alt`, `style`, `isSvg` — with no knowledge of how those values were derived.

When no explicit `sizes` is given, `use-auto-sizes` resolves the `sizes` attribute to
`auto` — but only when `loading` is not `eager`. `sizes="auto"` is only valid for
lazy-loaded images, so an eager image drops it (resolving to `null`). An explicit `sizes`
override is always honored regardless of `loading`.

When `focal-point` is enabled and **both** `focal-point-x` and `focal-point-y` are
present, the resolved `style` is `object-fit: cover; object-position: <x>% <y>%;`. The
`object-fit: cover` is required for `object-position` to have any visual effect. If the
focal point is disabled, or either coordinate is missing, no `style` is produced.

The resolved `width`/`height` describe the box the `<img>` occupies at its **intended**
size, derived from the requested size's registered spec (`width`, `height`, `crop`) and
the attachment's real source dimensions. When the source file is smaller than the
requested size the box still holds — the largest available file is upscaled by the browser
rather than collapsing the layout to the source's natural dimensions. WordPress never
upscales, so this derivation replaces WordPress's delivered dimensions:

- For a **crop** size the box is the target `width`×`height`. When the source cannot fill
  that box, the resolved `style` gains `object-fit: cover;` so the upscaled file covers the
  box at the intended ratio without distortion. When the source is large enough, WordPress
  delivered an exact crop, so the box equals the delivered dimensions and no `object-fit`
  is added.
- For a **non-crop** size the box keeps the source's own aspect ratio, scaled up to the
  target bound: width-only when the target height is `0`, otherwise the contain-fit of both
  target dimensions. The box shares the source ratio, so no `object-fit` is needed.

A focal-point style always takes precedence over an automatic `object-fit: cover;`. When
the requested size is not a registered Sproutset size, or the source dimensions are
unknown, the resolver leaves WordPress's delivered dimensions unchanged and adds no style.
This derivation is pure aspect-ratio math, isolated in `Images/PresentedDimensions`.

Rendering rules:

- When resolution returns `null`, or the resolved `src` is empty, **nothing** is emitted.
- For a raster source, the `<img>` carries `src`, `width`, `height`, `srcset`, `sizes`,
  `alt`, `style`, `loading`, `decoding` — with empty/`null` values dropped.
- For an SVG source (`isSvg === true`), the `<img>` carries only `src`, `alt`, `style`.
- `class` is a declared prop, so it is re-applied to the `<img>` through the attribute
  bag. This is required because Blade extracts a declared prop out of the attribute bag —
  a naive `{{ $attributes }}` dump would silently drop the consumer's `class`.
- Any other attribute (`id`, `data-*`, `aria-*`, `title`, …) passes through the attribute
  bag onto the `<img>` unchanged.

The container's default `ImageResolver` is the WordPress-backed resolver (see
`specs/service-provider.md`). `NullImageResolver` remains a boot-safe fallback that
returns `null`, so a request never fatally requires a working resolver. Tests bind a
fake `ImageResolver` to exercise every rendering rule with no WordPress runtime.

## Scenarios

```gherkin
Scenario: renders a raster image from the resolved view-model
  Given a resolver that returns a raster ResolvedImage with src, srcset, sizes, width, height, alt and style
  When the component is rendered
  Then an img tag is emitted carrying those attributes plus the default loading and decoding

Scenario: drops empty resolved attributes
  Given a resolver that returns a raster ResolvedImage whose srcset and style are null
  When the component is rendered
  Then the img tag omits the srcset and style attributes

Scenario: renders a reduced attribute set for an SVG source
  Given a resolver that returns a ResolvedImage with isSvg true
  When the component is rendered
  Then the img tag carries only src, alt and style and omits width, height, srcset, sizes, loading and decoding

Scenario: renders nothing when resolution returns null
  Given a resolver that returns null
  When the component is rendered
  Then no markup is emitted

Scenario: re-applies the declared class prop to the img
  Given a resolver that returns a raster ResolvedImage
  When the component is rendered with class "rounded shadow"
  Then the img class attribute carries both rounded and shadow

Scenario: passes arbitrary attributes through the attribute bag
  Given a resolver that returns a raster ResolvedImage
  When the component is rendered with id and data attributes
  Then those attributes appear unchanged on the img tag

Scenario: normalizes loose attribute input
  Given attribute values supplied as strings and other loose types
  When the input is normalized
  Then the ImageRequest carries the coerced typed values and documented defaults

Scenario: renders nothing when the boot-safe null resolver is bound
  Given the boot-safe NullImageResolver is bound as the resolver
  When the component is rendered
  Then no markup is emitted

Scenario: renders nothing when the resolved source is empty
  Given a resolver that returns a ResolvedImage whose src is null
  When the component is rendered
  Then no markup is emitted

Scenario: applies consumer loading and decoding overrides
  Given a resolver that returns a raster ResolvedImage
  When the component is rendered with loading "eager" and decoding "sync"
  Then the img carries loading="eager" and decoding="sync"

Scenario: emits auto sizes for a lazy-loaded image
  Given a request with no explicit sizes, auto sizes enabled and lazy loading
  When the sizes attribute is resolved
  Then the resolved sizes is "auto"

Scenario: omits auto sizes for an eager-loaded image
  Given a request with no explicit sizes, auto sizes enabled and eager loading
  When the sizes attribute is resolved
  Then the resolved sizes is null

Scenario: emits object-fit and object-position from a focal point
  Given a request with focal point enabled and both coordinates set
  When the focal point style is computed
  Then the style is "object-fit: cover; object-position: <x>% <y>%;"

Scenario: emits no focal style when a coordinate is missing
  Given a request with focal point enabled but a missing coordinate
  When the focal point style is computed
  Then no style is produced

Scenario: upscales a crop size to its target box when the source is too small
  Given a crop size larger than the attachment's source file
  When the image is resolved
  Then the width and height are the target box and the style carries object-fit: cover

Scenario: upscales a non-crop size preserving the source aspect ratio
  Given a non-crop size larger than the attachment's source file
  When the image is resolved
  Then the width and height scale the source ratio up to the target bound and no object-fit is added

Scenario: leaves a big-enough source at its delivered dimensions
  Given a size the attachment's source file can satisfy
  When the image is resolved
  Then the width and height are WordPress's delivered dimensions and no object-fit is added

Scenario: keeps WordPress dimensions for an unregistered size
  Given a requested size that Sproutset has not registered
  When the image is resolved
  Then the resolver leaves the delivered width and height unchanged
```

## Acceptance criteria

Each scenario above maps 1:1 to a Pest test:

| Scenario | Pest test |
| --- | --- |
| `renders a raster image from the resolved view-model` | `tests/Feature/ImageComponentTest.php` → `it('renders a raster image from the resolved view-model')` |
| `drops empty resolved attributes` | `tests/Feature/ImageComponentTest.php` → `it('drops empty resolved attributes')` |
| `renders a reduced attribute set for an SVG source` | `tests/Feature/ImageComponentTest.php` → `it('renders a reduced attribute set for an SVG source')` |
| `renders nothing when resolution returns null` | `tests/Feature/ImageComponentTest.php` → `it('renders nothing when resolution returns null')` |
| `re-applies the declared class prop to the img` | `tests/Feature/ImageComponentTest.php` → `it('re-applies the declared class prop to the img')` |
| `passes arbitrary attributes through the attribute bag` | `tests/Feature/ImageComponentTest.php` → `it('passes arbitrary attributes through the attribute bag')` |
| `normalizes loose attribute input` | `tests/Unit/ImageInputNormalizerTest.php` → `it('normalizes loose attribute input')` |
| `renders nothing when the boot-safe null resolver is bound` | `tests/Feature/ImageComponentTest.php` → `it('renders nothing when the boot-safe null resolver is bound')` |
| `renders nothing when the resolved source is empty` | `tests/Feature/ImageComponentTest.php` → `it('renders nothing when the resolved source is empty')` |
| `applies consumer loading and decoding overrides` | `tests/Feature/ImageComponentTest.php` → `it('applies consumer loading and decoding overrides')` |
| `emits auto sizes for a lazy-loaded image` | `tests/Unit/ResponsiveSizesTest.php` → `it('emits auto when auto sizes are enabled and no override is given')` |
| `omits auto sizes for an eager-loaded image` | `tests/Unit/ResponsiveSizesTest.php` → `it('omits auto sizes when eager loading is requested')` |
| `emits object-fit and object-position from a focal point` | `tests/Unit/FocalPointPositionTest.php` → `it('maps focal coordinates to an object-fit and object-position style')` |
| `emits no focal style when a coordinate is missing` | `tests/Unit/FocalPointPositionTest.php` → `it('returns null when a coordinate is missing')` |
| `upscales a crop size to its target box when the source is too small` | `tests/Integration/WpImageResolverTest.php` → `test_upscales_a_crop_size_to_its_target_box_with_object_fit_cover` |
| `upscales a non-crop size preserving the source aspect ratio` | `tests/Integration/WpImageResolverTest.php` → `test_upscales_a_non_crop_size_preserving_the_source_aspect_ratio` |
| `leaves a big-enough source at its delivered dimensions` | `tests/Integration/WpImageResolverTest.php` → `test_leaves_a_big_enough_source_at_its_delivered_dimensions` |
| `keeps WordPress dimensions for an unregistered size` | `tests/Integration/WpImageResolverTest.php` → `test_keeps_wordpress_dimensions_for_an_unregistered_size` |

The pure aspect-ratio math in `Images/PresentedDimensions` is covered exhaustively by
`tests/Unit/PresentedDimensionsTest.php` (crop too-small, crop big-enough, non-crop
width-bound, non-crop contain-fit, source larger than target, and the null guards for
zero source dimensions and unknown sizes).
