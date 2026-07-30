# Core image size options

Sproutset makes WordPress's four core image sizes reflect `config('sproutset.image_sizes')` without writing the database, and locks the matching fields on **Settings → Media** so they can't be edited by hand. The `ImageSizeRegistrar` already re-registers these sizes via `add_image_size()`, so actual subsize generation uses config values; this feature closes the remaining gap where `get_option()` and the Media settings screen still read WordPress's own stored core-size values.

## Behavior

WordPress stores its four core sizes in options: `thumbnail_size_w/h` and `thumbnail_crop`, `medium_size_w/h`, `medium_large_size_w/h`, and `large_size_w/h`. Only `thumbnail` has a core crop option — WordPress has no `medium_crop`/`large_crop`.

**Overriding reads.** For each core size present in the config, Sproutset registers a `pre_option_{name}` filter returning the config value, short-circuiting the database read. `get_option('medium_size_w')` therefore returns the config width in every context — front-end, admin, REST, and CLI — with no database writes. Config wins on read even if a stored row differs. A core size absent from the config is not filtered, so WordPress keeps owning it.

Values come from the shared `ImageSizeConfigNormalizer`: `width`/`height` coerce to non-negative integers and `crop` maps to `1` or `0` to match WordPress's stored format. A proportional dimension (`0`, e.g. `medium_large` height) is filtered to `0`, which matches WordPress's own default. Only the four core sizes map to options; custom sizes and `@Nx` variants have no core option and are ignored.

**Locking the fields.** On the Media settings screen only, Sproutset prints a small inline script from `admin_footer-options-media.php` that disables the inputs for the config-managed core sizes (`#thumbnail_size_w`, `#thumbnail_size_h`, `#thumbnail_crop`, `#medium_size_w`, `#medium_size_h`, `#large_size_w`, `#large_size_h`) and appends a short note that the sizes are managed by Sproutset configuration. `medium_large` has no core UI field, so it is never in this set. Only sizes present in the config are locked. Disabled inputs are not submitted, so the form cannot overwrite the options — and the read filter would win regardless.

**Boot safety.** Both pieces are wired in `SproutsetServiceProvider::packageBooted()` behind the existing `function_exists('add_action')` guard, and only call `add_filter`/`add_action` on the boot path. No admin-only function runs on boot, so front-end requests stay safe.

## Scenarios

```gherkin
Scenario: Emits option overrides for a core size
  Given a config with a thumbnail size defining width, height and crop
  When the overrides are computed
  Then the result maps thumbnail_size_w, thumbnail_size_h and thumbnail_crop to those values

Scenario: Maps crop to one or zero
  Given a config thumbnail with crop true and another with crop false
  When the overrides are computed
  Then thumbnail_crop is 1 for the cropping size and 0 for the non-cropping size

Scenario: Skips a core size absent from config
  Given a config that omits the large size
  When the overrides are computed
  Then no large_size_w or large_size_h override is emitted

Scenario: Ignores non-core sizes
  Given a config with a custom size and an @Nx variant
  When the overrides are computed
  Then only the four core sizes produce option overrides

Scenario: Lists the locked input ids for managed sizes
  Given a config with thumbnail, medium and large
  When the locked input ids are computed
  Then they include the thumbnail, medium and large width, height and thumbnail crop inputs and exclude medium_large

Scenario: Overrides get_option for a managed core size
  Given the registered core image size options for a config medium width
  When get_option('medium_size_w') is read
  Then it returns the config width without a database write

Scenario: Leaves an unmanaged core size at the WordPress value
  Given a config that omits the large size and a stored large_size_w
  When the core image size options are registered
  Then get_option('large_size_w') still returns the stored value

Scenario: Disables the managed Media settings fields
  Given the registered Media settings lock for a config with thumbnail and medium
  When the options-media admin footer is rendered
  Then the output disables the thumbnail and medium inputs and shows the managed-by-configuration note

Scenario: Resolves the collaborators from the container
  Given the booted service provider
  When CoreImageSizeOptions and MediaSettingsLock are resolved from the container
  Then each returns an instance of its class
```

## Acceptance criteria

Each scenario above maps 1:1 to a Pest test:

| Scenario | Pest test |
| --- | --- |
| `Emits option overrides for a core size` | `tests/Unit/CoreImageSizeOptionsTest.php` → `it('maps a core size to its width height and crop options')` |
| `Maps crop to one or zero` | `tests/Unit/CoreImageSizeOptionsTest.php` → `it('maps crop to one or zero')` |
| `Skips a core size absent from config` | `tests/Unit/CoreImageSizeOptionsTest.php` → `it('skips a core size absent from config')` |
| `Ignores non-core sizes` | `tests/Unit/CoreImageSizeOptionsTest.php` → `it('ignores custom sizes and variants')` |
| `Lists the locked input ids for managed sizes` | `tests/Unit/MediaSettingsLockTest.php` → `it('lists the locked input ids and excludes medium_large')` |
| `Overrides get_option for a managed core size` | `tests/Integration/CoreImageSizeOptionsTest.php` → `test_overrides_get_option_for_a_managed_core_size` |
| `Leaves an unmanaged core size at the WordPress value` | `tests/Integration/CoreImageSizeOptionsTest.php` → `test_leaves_an_unmanaged_core_size_at_the_wordpress_value` |
| `Disables the managed Media settings fields` | `tests/Integration/MediaSettingsLockTest.php` → `test_disables_the_managed_media_settings_fields` |
| `Resolves the collaborators from the container` | `tests/Feature/CoreImageSizeOptionsTest.php` → `it('resolves the core image size collaborators from the container')` |
