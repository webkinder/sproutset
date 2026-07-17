# Image size registration

Sproutset registers the project's WordPress image sizes from configuration. `config('sproutset.image_sizes')` is the complete roster: on each request every configured size is registered via `add_image_size()`, and any size Sproutset previously registered is stripped first. This is what makes the project's sizes visible to `wp_get_registered_image_subsizes()`, which the resolver and `OnDemandSizeGenerator` rely on.

## Behavior

Each configured size has `width`, `height` (0 = proportional), and `crop` (true = hard crop). An optional `srcset` list of positive multipliers expands into extra `@Nx` variant sizes (e.g. `large@2x`) whose dimensions are the base scaled by the multiplier and whose crop is inherited.

Normalization coerces `width`/`height` to non-negative integers and `crop` to a boolean, drops malformed entries (non-string keys or non-array values), and ignores `srcset` multipliers that are non-numeric or not greater than zero. A variant of a base size whose dimension is `0` keeps that dimension at `0`.

Registration strips every currently-registered subsize (`remove_image_size()` over `wp_get_registered_image_subsizes()`), then registers each normalized entry. Because this runs on `after_setup_theme` at priority 10, the "complete roster" holds for sizes registered at priority ≤ 10; sizes other plugins register later still apply. WordPress core sizes (thumbnail/medium/medium_large/large) are option-driven and survive the strip unless the config overrides them by name. The package ships those four as defaults so a fresh install keeps working.

## Scenarios

```gherkin
Scenario: Normalizes a base size
  Given a config with one size defining width, height and crop
  When the config is normalized
  Then the result maps that size name to its width, height and crop

Scenario: Expands srcset multipliers into variants
  Given a config size with an srcset list
  When the config is normalized
  Then each multiplier adds an "@Nx" variant whose dimensions are the base scaled by the multiplier

Scenario: Keeps a zero base dimension at zero in variants
  Given a config size whose height is 0 with an srcset multiplier
  When the config is normalized
  Then the variant's height is 0

Scenario: Filters invalid srcset multipliers
  Given a config size whose srcset contains non-numeric and non-positive values
  When the config is normalized
  Then only the positive numeric multipliers produce variants

Scenario: Drops malformed size entries
  Given a config with a non-array value and a non-string key
  When the config is normalized
  Then only the well-formed string-keyed array entries remain

Scenario: Registers configured sizes and variants
  Given a raw config with a size and an srcset multiplier
  When the registrar registers it
  Then wp_get_registered_image_subsizes() contains the size and its variant with matching dimensions

Scenario: Strips previously registered sizes
  Given a size already registered with add_image_size()
  When the registrar registers a config that omits it
  Then that size is no longer registered

Scenario: Ships default image sizes
  Given the booted service provider
  When config('sproutset.image_sizes') is read
  Then it contains thumbnail, medium, medium_large and large

Scenario: Registrar resolves from the container
  Given the booted service provider
  When ImageSizeRegistrar is resolved from the container
  Then it returns an ImageSizeRegistrar instance

Scenario: Normalizes the shipped default config
  Given the shipped config('sproutset.image_sizes') defaults
  When the config is normalized
  Then it yields the four base sizes plus @0.5x and @2x variants for medium_large and large, with large@2x sized 2048x2048
```

## Acceptance criteria

Each scenario above maps 1:1 to a test:

| Scenario | Test |
| --- | --- |
| `Normalizes a base size` | `tests/Unit/ImageSizeConfigNormalizerTest.php` → `it('normalizes width height and crop for a base size')` |
| `Expands srcset multipliers into variants` | `tests/Unit/ImageSizeConfigNormalizerTest.php` → `it('expands srcset multipliers into @Nx variants')` |
| `Keeps a zero base dimension at zero in variants` | `tests/Unit/ImageSizeConfigNormalizerTest.php` → `it('keeps a zero base dimension at zero in variants')` |
| `Filters invalid srcset multipliers` | `tests/Unit/ImageSizeConfigNormalizerTest.php` → `it('filters non-numeric and non-positive srcset multipliers')` |
| `Drops malformed size entries` | `tests/Unit/ImageSizeConfigNormalizerTest.php` → `it('drops entries with a non-array value or non-string key')` |
| `Registers configured sizes and variants` | `tests/Integration/ImageSizeRegistrarTest.php` → `test_registers_configured_sizes_and_their_variants` |
| `Strips previously registered sizes` | `tests/Integration/ImageSizeRegistrarTest.php` → `test_strips_previously_registered_sizes` |
| `Ships default image sizes` | `tests/Feature/ImageSizeRegistrationTest.php` → `it('ships the default image sizes in config')` |
| `Registrar resolves from the container` | `tests/Feature/ImageSizeRegistrationTest.php` → `it('resolves the image size registrar from the container')` |
| `Normalizes the shipped default config` | `tests/Feature/ImageSizeRegistrationTest.php` → `it('normalizes the shipped default config into base sizes and variants')` |
