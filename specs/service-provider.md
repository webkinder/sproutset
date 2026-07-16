# Service Provider

Sproutset registers itself with the Acorn/Laravel container through a single service
provider built on `spatie/laravel-package-tools`. This is the package's entry point:
consuming Acorn projects auto-discover it, and it wires up the package's configuration.

## Behavior

`Webkinder\Sproutset\SproutsetServiceProvider` extends
`Spatie\LaravelPackageTools\PackageServiceProvider` and configures the package as
`sproutset` with a config file. On registration the provider merges `config/sproutset.php`
under the `sproutset` config key, so consumers read settings via `config('sproutset.*')`
and can publish the file with `vendor:publish` / `acorn vendor:publish` (tag
`sproutset-config`). Acorn discovers the provider through the `extra.acorn.providers`
entry in `composer.json`, so no manual registration is required in a consuming project.

## Scenarios

```gherkin
Scenario: Provider boots under the container
  Given a Laravel/Acorn application with Sproutset installed
  When the application boots
  Then the SproutsetServiceProvider is loaded

Scenario: Config file is merged
  Given the SproutsetServiceProvider has registered
  When config('sproutset') is read
  Then it returns the merged package configuration array
```

## Acceptance criteria

Each scenario above maps 1:1 to a Pest test:

| Scenario | Pest test |
| --- | --- |
| `Provider boots under the container` | `tests/Feature/ServiceProviderTest.php` → `it('registers the sproutset service provider')` |
| `Config file is merged` | `tests/Feature/ServiceProviderTest.php` → `it('merges the sproutset config file')` |
