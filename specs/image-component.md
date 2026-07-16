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

WordPress-backed resolution is **out of scope for this step**. The package ships a
`NullImageResolver` (returns `null`, so the front end boots without fatally requiring a
resolver), replaced by the real WordPress-backed `ImageResolver` in a later step. Tests
bind a fake `ImageResolver` to exercise every rendering rule with no WordPress runtime.

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
