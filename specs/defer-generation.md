# Deferred Upload-Time Size Generation

An opt-in mode that stops WordPress from eagerly generating Sproutset's `@Nx` srcset variants
at upload, letting the existing on-demand path produce them lazily at render instead. It
exists because eager generation of the whole roster (30+ Imagick passes off one large source,
the widest of them the `@Nx` retina variants) can exhaust PHP's `max_execution_time` — most
visibly on batch imports that loop `media_handle_sideload()`. Backward-compatible: default
off, current behavior unchanged until a project opts in.

Every configured size's **base** entry stays eager, so a size always has at least its base
file after upload; only the `@Nx` variants are deferred. That keeps the front end from ever
falling back to the full-size original, and keeps the deferred work off the widest, most
expensive resizes.

## Behavior

The top-level config key `defer_generation` (bool, default `false`) gates the feature.

- **`false` (default):** no behavior change. Every registered size is generated eagerly at
  upload exactly as before. The feature is a guaranteed no-op when off — no filter is
  registered.

- **`true`:** Sproutset registers an `intermediate_image_sizes_advanced` filter that removes
  its **deferrable** sizes from the set WordPress is about to generate during
  `wp_generate_attachment_metadata()`. Removed sizes are *not* unregistered — they remain in
  `wp_get_registered_image_subsizes()`, so the on-demand path still finds and generates them
  at render time when `<x-sproutset-image>` requests the size.

### The deferrable set

The library decides what is safe to defer; the consumer does not choose per size.

- **Kept eager:** every configured size's **base** entry — the four WordPress core sizes and
  every custom size (e.g. `hero`, `product`), but only the base, never the `@Nx` variants.
  Base sizes are cheap relative to the retina variants and must exist after upload so that WP
  core render paths (admin media library, block/classic editor, WooCommerce emails) and a
  cap-exhausted front end always have a real intermediate size to fall back to rather than the
  full-size original.

- **Deferred:** every `@Nx` srcset variant (`large@2x`, `hero@1.5x`, `product@2x`, …). These
  are the widest, most expensive resizes and are only ever consumed through the component's
  srcset, which the on-demand path rebuilds at render.

- **Never touched:** sizes Sproutset did not register (WooCommerce `woocommerce_*`, Gravity
  Forms `gform-*`, any other plugin/theme size). The filter only removes keys matching
  Sproutset's own normalized roster; all other keys pass through untouched.

If the roster has no `@Nx` variants, enabling the feature registers no filter — there is
nothing to defer, so it is a no-op.

### Render-time family generation

When `<x-sproutset-image>` resolves a raster image, the on-demand path ensures the requested
size's whole **family** — its base entry and every registered `@Nx` variant — before building
the srcset, so a deferred variant is generated the first time the size is rendered and the
front-end srcset stays complete (retina candidates included). The base is ensured first, so if
the per-request generation cap is reached the base is the size most likely to already exist.

### Boot safety

Registering the filter is front-end safe; its callback only runs inside
`wp_generate_attachment_metadata()` (upload / regenerate / CLI). The callback does pure array
manipulation — no admin-only WP calls, no I/O — and never fatals a request.

### Known tradeoffs

- `OnDemandSizeGenerator` caps at 10 generations per request. On a cold, image-dense page
  (e.g. a large product grid) the `@Nx` variants beyond the cap are not generated on that
  request; because every base size is eager, those images still render at their base size
  (srcset simply lacks the not-yet-generated retina candidates) and the missing variants are
  produced on subsequent requests (self-healing). The front end never falls back to the
  full-size original.
- AVIF is already fully on-demand and is unaffected.

## Scenarios

```gherkin
Scenario: only @Nx variants are deferrable
  Given a roster with core sizes, custom sizes and @Nx srcset variants
  When the deferrable set is computed
  Then every @Nx variant is listed
  And no base size (core or custom) is listed

Scenario: applying the filter removes only @Nx variants
  Given a size set containing base sizes, @Nx variants and foreign (plugin) sizes
  When the deferrable removal is applied
  Then every @Nx variant is removed
  And every base size (core and custom) remains
  And foreign sizes (woocommerce_*, gform-*) remain

Scenario: a roster without variants removes nothing
  Given a roster whose sizes declare no srcset multipliers
  When the deferrable removal is applied to a size set
  Then the size set is returned unchanged

Scenario: enabled registers the filter and defers the variants
  Given defer_generation is true
  When register runs and wp_generate_attachment_metadata applies the filter
  Then the @Nx variants are removed from the eager set
  And base sizes and foreign sizes remain

Scenario: disabled registers no filter
  Given defer_generation is false
  When register runs
  Then Sproutset adds no intermediate_image_sizes_advanced filter

Scenario: enabled with no variants registers no filter
  Given defer_generation is true
  And the roster declares no srcset multipliers
  When register runs
  Then Sproutset adds no intermediate_image_sizes_advanced filter

Scenario: deferred variants stay registered for on-demand
  Given defer_generation is true and the roster is registered
  When an @Nx variant is deferred from eager generation
  Then it is still present in wp_get_registered_image_subsizes()

Scenario: render generates the whole family on-demand
  Given a size whose @Nx variants have not been generated
  When the component resolves that size
  Then the base size and every registered @Nx variant are generated
  And the base size is generated first
```

## Acceptance criteria

Each scenario maps 1:1 to a test. Pure classification/removal logic lives in the fast **Unit**
lane (no WordPress); filter registration, firing, family generation and WP interaction live in
the **Integration** lane (real WordPress), mirroring `SrcsetWidthLimit`.

| Scenario | Test |
| --- | --- |
| `only @Nx variants are deferrable` | `tests/Unit/EagerGenerationDeferralTest.php` → `it('lists only @Nx variants as deferrable, never base sizes')` |
| `applying the filter removes only @Nx variants` | `tests/Unit/EagerGenerationDeferralTest.php` → `it('removes only @Nx variants, keeping base and foreign sizes')` |
| `a roster without variants removes nothing` | `tests/Unit/EagerGenerationDeferralTest.php` → `it('returns the size set unchanged for a roster without variants')` |
| `enabled registers the filter and defers the variants` | `tests/Integration/EagerGenerationDeferralTest.php` → `test_enabled_defers_variant_sizes_from_eager_generation` |
| `disabled registers no filter` | `tests/Integration/EagerGenerationDeferralTest.php` → `test_disabled_registers_no_filter` |
| `enabled with no variants registers no filter` | `tests/Integration/EagerGenerationDeferralTest.php` → `test_enabled_registers_no_filter_when_roster_has_no_variants` |
| `deferred variants stay registered for on-demand` | `tests/Integration/EagerGenerationDeferralTest.php` → `test_deferred_variants_remain_registered_for_on_demand` |
| `render generates the whole family on-demand` | `tests/Integration/OnDemandSizeGeneratorTest.php` → `test_ensure_family_generates_base_and_variants` |
