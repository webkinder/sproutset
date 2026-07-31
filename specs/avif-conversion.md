# AVIF conversion

Sproutset can serve an AVIF version of any image rendered through `<x-sproutset-image>`, layered on top of the original as a `<picture>` source with the original-format `<img>` as fallback. It is opt-in, applies only to images sproutset itself renders, and degrades to identical-to-today markup whenever AVIF is disabled, unsupported by the server, or fails to encode. It never touches WordPress's global image pipeline, so favicons, `og:image`, admin thumbnails, and email images are never affected.

## Behavior

**Scope and principle.** AVIF is *additive*. The original-format `<img>` is always emitted and is always the source of truth; an AVIF `<source>` is layered above it. Because sproutset only augments its own rendered component — and never filters WordPress's global subsize generation — no favicon, social-share, admin, or system image can be replaced by AVIF. When AVIF produces nothing, the component emits the exact same single `<img>` as today.

**Opt-in configuration.** A new `avif` block in `config/sproutset.php`:

```php
'avif' => [
    'enabled' => false,   // opt-in; false is a guaranteed no-op
    'quality' => 50,      // 0–100 encode quality
],
```

When `enabled` is `false`, no probe runs, no files are generated, and no `avifSrcset` is produced — output is byte-identical to the pre-feature package. No per-size overrides exist; `quality` is global.

**Capability detection (`AvifSupport`).** The single failure mode this guards against: a server whose image editor can *read* AVIF but cannot *write* it (GD with `imageavif()` present but no libavif encoder; Imagick listing AVIF in `queryFormats()` for read only). Declared capability — including WordPress's `WP_Image_Editor::supports_mime_type()` — is not trusted. Instead `AvifSupport::isSupported()` performs a **real-encode probe**: it writes a tiny 1×1 PNG to a temporary file (via PHP's own `tempnam()`/`sys_get_temp_dir()` — never the admin-only `wp_tempnam`, so it is safe on any request), asks the WordPress image editor WordPress would actually choose to `save()` it as `image/avif`, reads the resulting bytes back, and verifies they carry a valid AVIF `ftyp` signature with an `avif` or `avis` brand. The probe source is a hardcoded PNG byte string rather than a GD-generated image, so the probe does not depend on the GD extension and works on Imagick-only builds. It is a **truecolor (RGBA)** PNG: some GD builds reject a grayscale+alpha PNG in `imagecreatefromstring()`, which would make the probe a false negative on servers that can in fact write AVIF. Temporary files are always cleaned up in a `finally` block. The verdict is cached in a transient encoded as a `'1'`/`'0'` sentinel — never a raw boolean — because `get_transient()` returns `false` for a *missing* transient, which a boolean cache would misread as a stored "unsupported" verdict and never re-probe. Any thrown error resolves to unsupported. `AvifSupport` is an interface; a fake implementation is bound in tests.

**Variant generation (`AvifVariantGenerator`).** For an existing subsize file (`image-300x200.jpg`) the generator writes an AVIF sibling in the same directory (`image-300x200.avif`) via `wp_get_image_editor()->save($avifPath, 'image/avif')` with the configured quality. It only runs when `enabled` **and** `AvifSupport::isSupported()`. Generation is **lazy and bounded**: it enforces its own per-request generation cap, mirroring `OnDemandSizeGenerator`, so a single page cannot trigger unbounded encoding; siblings not produced this request fill in on later requests, and the fallback `<img>` covers any gap so nothing ever looks broken. It refuses to encode in three cases: an **animated GIF** source (AVIF would drop the animation), a source whose editor cannot be created, and a result that is **not smaller** than the source (which is discarded, avoiding the pathology of an AVIF larger than the original). On any encode failure it trips a **per-attachment fuse** — a marker written to the attachment's metadata — and never retries that attachment; the component then silently serves the original. All generation runs inside a `try/catch(Throwable)` for boot safety.

**Resolver output and responsive parity (`AvifSrcsetBuilder`).** `ResolvedImage` gains one nullable field, `avifSrcset`. In `WpImageResolver::resolveRaster()`, after the normal srcset is built, if AVIF is active the resolver walks each srcset candidate, ensures its `.avif` sibling (bounded and fused as above), and builds a parallel `avifSrcset` covering the **same widths** — giving the AVIF source true responsive/retina parity with the original. When the image has **no responsive srcset** — a single-size render such as a lone hard-crop whose aspect ratio no other subsize shares — the resolver falls back to a single-candidate AVIF source built from the primary `src` itself, so single-size images still receive AVIF rather than none. The same generator, per-request cap, per-attachment fuse, not-smaller-than-source discard, and all-or-nothing rule apply; with a single candidate, all-or-nothing means the AVIF source is emitted only if that one sibling encodes. The extension-swap and existence-filter logic is a pure `AvifSrcsetBuilder`, unit-tested in isolation. If AVIF is disabled, unsupported, or no sibling could be produced, `avifSrcset` is `null`. The AVIF `<source>` is emitted only when *every* original srcset width has a generated sibling — a partial AVIF srcset would let the browser silently resolve a lower-resolution AVIF candidate for a viewport the original srcset would have served at full resolution, so it is all-or-nothing: until every sibling exists, the plain `<img>` is served, while siblings continue to backfill across requests.

**Markup.** `image.blade.php` wraps the image in `<picture>` only when `avifSrcset` is present:

```blade
@if ($avifSrcset)
  <picture>
    <source type="image/avif" srcset="{{ $avifSrcset }}"@if($avifSizes) sizes="{{ $avifSizes }}"@endif>
    <img {{ $attributes->class($class)->merge($htmlAttributes) }}>
  </picture>
@else
  <img {{ $attributes->class($class)->merge($htmlAttributes) }}>
@endif
```

The `<img>` element and all its merged attributes are unchanged from today; `<picture>` is an inert wrapper with no box of its own. SVGs skip this path entirely via the resolver's existing SVG branch. The `<source>` repeats the same `sizes` string as the `<img>` so the browser selects the correct AVIF candidate. The only CSS caveat is that a direct-child selector (`.gallery > img`) becomes `.gallery > picture > img`.

**Lifecycle and cleanup (`AvifCleanup`).** AVIF siblings are sproutset-owned and are not listed in WordPress attachment metadata, so WordPress will not remove them. On `delete_attachment`, sproutset unlinks the attachment's `.avif` siblings, deriving them **deterministically** from the attachment's own metadata — the original file plus each subsize `file` in `wp_get_attachment_metadata()['sizes']`, extension-swapped to `.avif`. It deliberately does **not** wildcard-glob the base filename: WordPress dedups uploads (`image.jpg`, `image-1.jpg`), so a prefix glob would match a different live attachment's siblings and delete them. The trade-off is that a rare orphan left by a prior image edit is not swept, but such orphans are unreferenced and harmless.

**Service provider wiring.** `AvifSupport` binds to its WP implementation as a **singleton** so the transient-backed verdict is memoized per request. The generator and support seam are injected where needed. The `delete_attachment` cleanup hook is registered in `packageBooted()` inside the existing `function_exists('add_action')` guard. Nothing admin-only runs on the boot path; the probe and generation run only during a component render, never at boot.

## Scenarios

```gherkin
Scenario: Validates a real AVIF byte signature
  Given a byte string beginning with a valid ftyp box carrying an avif brand
  When the AVIF signature is validated
  Then it is accepted, and bytes without an avif/avis brand are rejected

Scenario: Reports unsupported when the encode probe yields no valid AVIF
  Given an AvifSupport probe whose in-memory encode returns non-AVIF bytes
  When support is queried
  Then it reports unsupported

Scenario: Builds a parallel AVIF srcset when every width has a sibling
  Given an original srcset whose every candidate has an existing AVIF sibling
  When the AVIF srcset is built
  Then each candidate maps to its .avif url at the same descriptor

Scenario: Yields no AVIF srcset when a width is missing a sibling
  Given an original srcset where one candidate has no AVIF sibling
  When the AVIF srcset is built
  Then the result is null, and generation was attempted for every candidate

Scenario: Yields no AVIF srcset when none exists
  Given an original srcset for which no AVIF sibling exists
  When the AVIF srcset is built
  Then the result is null

Scenario: Discards an AVIF that is not smaller than the source
  Given a generated AVIF variant larger than or equal to its source file
  When the variant is finalized
  Then the AVIF file is discarded and no sibling is recorded

Scenario: Skips an animated GIF source
  Given an animated GIF attachment
  When AVIF generation is attempted
  Then no AVIF is written and the source is left untouched

Scenario: Trips the per-attachment fuse on encode failure
  Given an attachment whose AVIF encode throws
  When AVIF generation is attempted and then attempted again
  Then a failure marker is recorded and the second attempt performs no encode

Scenario: Renders a picture element when an AVIF srcset is present
  Given a resolved image carrying an avifSrcset
  When the component is rendered
  Then the markup is a picture with an image/avif source and the original img fallback

Scenario: Renders a plain img when no AVIF srcset is present
  Given a resolved image with a null avifSrcset
  When the component is rendered
  Then the markup is a single img identical to the non-AVIF output

Scenario: Produces no AVIF when the feature is disabled
  Given avif.enabled is false
  When an image is resolved
  Then avifSrcset is null and no AVIF file is generated

Scenario: Produces no AVIF when the server cannot write AVIF
  Given avif.enabled is true and AvifSupport reports unsupported
  When an image is resolved
  Then avifSrcset is null and no AVIF file is generated

Scenario: Generates AVIF siblings and serves them when supported
  Given avif.enabled is true and a server that can write AVIF
  When an image with a responsive srcset is resolved
  Then an AVIF sibling exists for each generated candidate and avifSrcset covers the same widths

Scenario: Serves AVIF for a single-size image with no responsive srcset
  Given avif.enabled is true, a server that can write AVIF, and an attachment size with no responsive srcset
  When the image is resolved
  Then avifSrcset is a single-candidate AVIF source built from the primary src, and that .avif file exists

Scenario: Removes AVIF siblings when the attachment is deleted
  Given an attachment with generated AVIF siblings
  When the attachment is deleted
  Then its .avif sibling files are unlinked

Scenario: Resolves the AVIF collaborators from the container
  Given the booted service provider
  When AvifSupport and AvifVariantGenerator are resolved from the container
  Then each returns an instance of its bound class
```

## Acceptance criteria

Each scenario above maps 1:1 to a Pest test:

| Scenario | Pest test |
| --- | --- |
| `Validates a real AVIF byte signature` | `tests/Unit/AvifSignatureTest.php` → `it('accepts avif branded bytes and rejects others')` |
| `Reports unsupported when the encode probe yields no valid AVIF` | `tests/Unit/AvifSupportTest.php` → `it('reports unsupported when the probe yields no valid avif')` |
| `Builds a parallel AVIF srcset when every width has a sibling` | `tests/Unit/AvifSrcsetBuilderTest.php` → `it('maps every candidate to its avif sibling url at the same descriptor when all siblings exist')` |
| `Yields no AVIF srcset when a width is missing a sibling` | `tests/Unit/AvifSrcsetBuilderTest.php` → `it('returns null when a candidate is missing a sibling, after attempting generation for every candidate')` |
| `Yields no AVIF srcset when none exists` | `tests/Unit/AvifSrcsetBuilderTest.php` → `it('returns null when no sibling exists')` |
| `Discards an AVIF that is not smaller than the source` | `tests/Integration/AvifVariantGeneratorTest.php` → `test_discards_an_avif_that_is_not_smaller_than_the_source` |
| `Skips an animated GIF source` | `tests/Integration/AvifVariantGeneratorTest.php` → `test_skips_an_animated_gif_source` |
| `Trips the per-attachment fuse on encode failure` | `tests/Integration/AvifVariantGeneratorTest.php` → `test_trips_the_fuse_and_skips_retrying_after_an_encode_failure` |
| `Renders a picture element when an AVIF srcset is present` | `tests/Feature/AvifImageComponentTest.php` → `it('renders a picture with an avif source when an avif srcset is present')` |
| `Renders a plain img when no AVIF srcset is present` | `tests/Feature/AvifImageComponentTest.php` → `it('renders a plain img when no avif srcset is present')` |
| `Produces no AVIF when the feature is disabled` | `tests/Integration/AvifResolverTest.php` → `test_produces_no_avif_when_disabled` |
| `Produces no AVIF when the server cannot write AVIF` | `tests/Integration/AvifResolverTest.php` → `test_produces_no_avif_when_the_server_cannot_write_avif` |
| `Generates AVIF siblings and serves them when supported` | `tests/Integration/AvifVariantGeneratorTest.php` → `test_generates_avif_siblings_and_serves_them_when_supported` |
| `Serves AVIF for a single-size image with no responsive srcset` | `tests/Integration/AvifResolverTest.php` → `test_serves_avif_for_a_single_size_image_without_a_srcset` |
| `Removes AVIF siblings when the attachment is deleted` | `tests/Integration/AvifCleanupTest.php` → `test_removes_avif_siblings_when_the_attachment_is_deleted` |
| `Resolves the AVIF collaborators from the container` | `tests/Feature/AvifServiceProviderTest.php` → `it('resolves the avif collaborators from the container')` |
