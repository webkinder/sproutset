# Focal point

Sproutset lets an editor set a focal point once per image in the Media Library. From then on
the image is cropped and positioned around that point everywhere `<x-sproutset-image>` renders
it, with no per-usage configuration. A developer can override the focal point for a single
placement with the component's `focal-point` attributes. The feature is on by default and a
center (50/50) focal point is a no-op; a config flag disables the whole attachment-level feature.

## Behavior

**Rendering model.** `object-position` only has a visible effect when `object-fit: cover` forces
the image into a box, so the two mechanisms split by size type:

- **Hard-crop registered sizes** — the generated subsize file is physically re-cropped around the
  focal point, sourced from the original. Focal-correct across the whole `srcset`, no CSS.
- **CSS `object-fit: cover; object-position: x% y%`** — emitted only where cover is in play: an
  explicit per-call `focal-point` request, the crop-size upscale-too-small fallback, and SVG.
- **Non-crop sizes without explicit cover** — no focal effect.

**Precedence.** CSS position uses `explicit per-call coords → attachment metadata → center`.
Physical cropping uses `attachment metadata → center` only — a per-call override never re-crops
the shared subsize files, so one placement's override cannot leak into other uses.

**Storage.** Coordinates live in dedicated post meta (`_sproutset_focal_point_x` / `_y`, floats
0–100), surviving `wp media regenerate`. An applied-marker(`_sproutset_focal_applied`, a
`size → "x,y"` map) records which subsizes were cropped at which point.

**Lazy cropping + invalidation.** On resolving a raster image whose metadata focal point is
off-center, hard-crop subsizes are re-cropped from the original, bounded per request. The marker
skips subsizes already cropped at the current point. Changing the focal point in the picker, or
`wp_generate_attachment_metadata` firing (upload / regenerate), clears the marker so the next
render re-crops. A crop failure trips a per-attachment fuse and the image serves unchanged.

**Configuration.** `config('sproutset.focal_point')` (default `true`) gates the picker, metadata
honoring, and cropping. The explicit per-call attribute is independent of the flag.

Scenario: computes a crop window centred on the focal point
  Given an original larger than a square crop target and a focal point of 25/75
  When the crop window is computed
  Then the window matches the target aspect, is centred on the point, and is clamped to the original

Scenario: clamps the crop window to the original bounds
  Given a focal point at the far corner (100/100)
  When the crop window is computed
  Then the window origin is clamped so the window stays inside the original

Scenario: reads a stored focal point from attachment meta
  Given an attachment with stored focal coordinates
  When the focal point is read
  Then it returns the clamped coordinates

Scenario: treats an unset or center focal point as none
  Given an attachment with no stored focal point, or a stored 50/50
  When the focal point is read
  Then it returns none

Scenario: emits object-fit and object-position for a cover context
  Given an off-center focal point and a cover context
  When the focal style is computed
  Then the style is "object-fit: cover; object-position: <x>% <y>%;"

Scenario: emits plain object-fit cover for a center focal point in a cover context
  Given a center or absent focal point and a cover context
  When the focal style is computed
  Then the style is "object-fit: cover;"

Scenario: emits no style outside a cover context
  Given any focal point and no cover context
  When the focal style is computed
  Then no style is produced

Scenario: physically crops hard-crop subsizes from the attachment focal point
  Given an attachment with an off-center stored focal point
  When a raster image is resolved
  Then each hard-crop subsize is re-cropped and marked applied at that point

Scenario: skips subsizes already cropped at the current focal point
  Given hard-crop subsizes already marked applied at the current point
  When focal cropping runs again
  Then no subsize is re-cropped

Scenario: does not crop when the per-attachment fuse is tripped
  Given an attachment whose focal-crop fuse is set
  When focal cropping runs
  Then no subsize is cropped or marked

Scenario: an explicit per-call focal point overrides the stored one for CSS
  Given an attachment with a stored focal point and a request with explicit coordinates
  When the image is resolved
  Then the CSS object-position uses the explicit coordinates

Scenario: the feature is inert when disabled
  Given the focal_point config flag is false
  When a raster image with a stored focal point is resolved
  Then no cropping occurs and no focal style is produced

Scenario: saving the picker stores and clamps the focal point and invalidates crops
  Given posted focal coordinates outside 0–100 and an existing applied-marker
  When the attachment form is saved
  Then the coordinates are clamped and stored and the applied-marker is cleared

## Acceptance criteria

| Scenario | Test |
| --- | --- |
| (filled in as tasks land; completed in Task 11) | |
