# Original srcset fallback

Sharpens source-limited hard-crop sizes on high-DPR screens. When a `crop: true` size with
`srcset` multipliers cannot generate its top-multiplier crop because the source is too small
to crop at that size, WordPress caps the emitted srcset at the largest crop it *could*
generate and excludes the differently-shaped original by aspect-ratio tolerance. Sproutset
then appends the full original as an extra srcset candidate, boxed with `object-fit: cover`,
so a high-DPR browser can pick the higher-resolution original instead of an upscaled 2× crop.
This extends the existing "source smaller than the crop box" cover behavior (see
[`image-component.md`](image-component.md)) to the sibling case where the source is larger
than the largest generated crop but still cannot reach the top multiplier.

## Behavior

While resolving a **raster** image for a **registered `crop: true` size**, after WordPress
produces the srcset, Sproutset (in `Images/OriginalSrcsetFallback`, pure logic) decides
whether to append the original. All inputs are derivable at resolve time; no new config key
or component attribute is introduced — the behavior is automatic.

Definitions for a crop box of ratio `r = targetWidth / targetHeight`:

- `E` = the horizontal source resolution the original contributes under `object-fit: cover`:
  `E = min(originalWidth, round(originalHeight × r))`. A tall original is width-bound
  (`E = originalWidth`); a wide-short original is height-bound (`E` small).
- `topTarget` = the largest width among registered subsizes keyed `sizeName` or
  `sizeName@Nx` — the crop the config wanted.
- `maxCandidate` = the largest `w` descriptor in the srcset WordPress actually emitted.

**Injection rule.** The original is appended **iff** `maxCandidate < topTarget` (the top
multiplier crop was not generated) **and** `E > maxCandidate` (the original genuinely beats
the largest crop). The candidate uses the original URL with descriptor `min(E, topTarget)` —
never advertised beyond the largest crop the size configures, so it stays within the srcset
ceiling `SrcsetWidthLimit` may have lifted for retina widths above 2048 (a hardcoded 2048 cap
would truncate exactly those retina configs). `maxCandidate` is read from the `Nw` descriptor
token of each candidate, not by scanning the whole string, so digits inside a filename
(`banner-1200w.jpg`) are never mistaken for a descriptor. An original already present in the
srcset is not duplicated.

When (and only when) the original is injected, `cover` is forced true, so the resolved
`style` gains `object-fit: cover` (plus `object-position` from a focal point via
`FocalPointPosition::forCover`). `src`, `width`, and `height` are unchanged — `src` stays the
correct-ratio base crop, width/height stay the target box. The augmented srcset is produced
**before** the AVIF srcset, so the AVIF srcset gains an AVIF sibling of the original.

The rule self-limits: a pathological wide-short original has `E ≤ maxCandidate` and is
rejected; a size with no `@Nx` multipliers has `topTarget` equal to its base width, already
met, so it never triggers; non-crop sizes, SVGs, and unregistered sizes are untouched.

**Trade-offs and limitations.**

- The injected candidate serves the full original bytes at descriptor `E`. `E` is the cover
  contribution, not the file's pixel width, so at low DPR a CSS slot between the largest crop
  and `E` will download the full original where it previously upscaled a crop — a deliberate
  quality-over-bytes choice for source-limited crops (Option A), not a bug.
- `maxCandidate` reflects only the subsizes WordPress has actually generated. An attachment
  uploaded before a size was registered keeps an incomplete srcset (on-demand generation
  makes only the base size, never `@Nx` siblings), so injection may fire and serve the
  original in place of a crop the source could produce. Regenerating the images
  (`wp media regenerate`) restores the crops and the normal selection.
- Feeding the original into the AVIF srcset couples the heaviest AVIF encode to the
  all-or-nothing `AvifSrcsetBuilder`: if the original's AVIF sibling fails, the whole AVIF
  srcset is dropped and the browser falls back to the (sharp) original in the raster srcset.

## Scenarios

```gherkin
Scenario: Injects the original and forces cover when the top multiplier crop is source-limited
  Given a crop size with emitted candidates up to 524w, topTarget 786 and a 700x900 original
  When the fallback is computed
  Then the original URL is appended at 700w and cover is forced true

Scenario: Advertises the effective width for a wide-short original
  Given a crop box ratio 262/332 and a 1200x800 original
  When the fallback is computed
  Then the appended descriptor is round(800 * 262/332) = 631w

Scenario: Does not inject a pathological wide-short original that would not help
  Given a largest candidate of 524w and a 3000x400 original whose effective width is 316
  When the fallback is computed
  Then no candidate is appended and cover is unchanged

Scenario: Does not inject when the top multiplier crop already exists
  Given an emitted srcset whose largest candidate equals topTarget
  When the fallback is computed
  Then no candidate is appended

Scenario: Does not inject when the original does not beat the largest crop
  Given a largest candidate of 524w and an original whose effective width is 500
  When the fallback is computed
  Then no candidate is appended

Scenario: Does not truncate a large valid original below a lifted retina ceiling
  Given a retina crop with candidates up to 2000w, topTarget 3000 and a 2600x2600 original
  When the fallback is computed
  Then the appended descriptor is 2600w, not a hardcoded 2048

Scenario: Caps the injected descriptor at the top target
  Given an original whose effective width exceeds topTarget
  When the fallback is computed
  Then the appended descriptor equals topTarget

Scenario: Ignores digits inside candidate urls when reading the largest descriptor
  Given a srcset whose filenames contain a "1200w" segment and real descriptors up to 524w
  When the fallback is computed
  Then the original is injected at 700w (the 1200 in the filename is not read as a descriptor)

Scenario: Appends the original as the only candidate for an empty srcset
  Given an empty srcset
  When the fallback is computed
  Then the result srcset is the original candidate alone

Scenario: Does not duplicate an original already present in the srcset
  Given an emitted srcset that already contains the original URL
  When the fallback is computed
  Then the srcset is returned unchanged

Scenario: Returns unchanged when a dimension is unknown
  Given an original with a zero dimension
  When the fallback is computed
  Then the srcset is returned unchanged

Scenario: Resolves a source-limited crop with the injected original and object-fit cover
  Given a registered crop size whose top multiplier crop the source cannot produce
  When the image is resolved
  Then srcset gains the original candidate, style is object-fit: cover, and src/width/height are the base crop box

Scenario: Leaves a non-crop size without an injected original
  Given a registered non-crop size larger than the source
  When the image is resolved
  Then no original-fallback candidate is injected and no cover style is forced

Scenario: Feeds the injected original into the AVIF srcset
  Given AVIF enabled and supported and a source-limited crop that injects the original
  When the image is resolved
  Then the AVIF srcset carries an AVIF sibling of the original at the injected descriptor
```

## Acceptance criteria

Each scenario above maps 1:1 to a test:

| Scenario | Test |
| --- | --- |
| `Injects the original and forces cover when the top multiplier crop is source-limited` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('appends the original and forces cover when the top multiplier crop is source-limited')` |
| `Advertises the effective width for a wide-short original` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('advertises the effective width for a wide-short original')` |
| `Does not inject a pathological wide-short original that would not help` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('does not inject a pathological wide-short original that would not help')` |
| `Does not inject when the top multiplier crop already exists` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('does not inject when the top multiplier crop already exists')` |
| `Does not inject when the original does not beat the largest crop` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('does not inject when the original does not beat the largest crop')` |
| `Does not truncate a large valid original below a lifted retina ceiling` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('does not truncate a large valid original below a lifted retina ceiling')` |
| `Caps the injected descriptor at the top target` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('caps the injected descriptor at the top target')` |
| `Ignores digits inside candidate urls when reading the largest descriptor` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('ignores digits inside candidate urls when reading the largest descriptor')` |
| `Appends the original as the only candidate for an empty srcset` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('appends the original as the only candidate for an empty srcset')` |
| `Does not duplicate an original already present in the srcset` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('does not duplicate an original already present in the srcset')` |
| `Returns unchanged when a dimension is unknown` | `tests/Unit/OriginalSrcsetFallbackTest.php` → `it('returns unchanged when a dimension is unknown')` |
| `Resolves a source-limited crop with the injected original and object-fit cover` | `tests/Integration/WpImageResolverTest.php` → `test_injects_the_original_for_a_source_limited_crop_with_object_fit_cover` |
| `Leaves a non-crop size without an injected original` | `tests/Integration/WpImageResolverTest.php` → `test_leaves_a_non_crop_size_without_an_injected_original` |
| `Feeds the injected original into the AVIF srcset` | `tests/Integration/AvifResolverTest.php` → `test_feeds_the_injected_original_into_the_avif_srcset` |
