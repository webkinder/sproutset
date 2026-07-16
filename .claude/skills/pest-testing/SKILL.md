---
name: pest-testing
description: "Use this skill for Pest PHP testing in this Acorn/WordPress library (runs under Testbench). Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit), or needs architecture tests. Covers: test()/it()/expect() syntax, datasets, mocking, arch(), and Pest 4 features. Do not use for non-test PHP code. This package ships a Blade image component but no HTTP routes or standalone pages, so full browser/E2E tests do not apply — render the component directly and assert on its markup."
license: MIT
---

# Pest Testing 4

## Documentation

See the Pest 4 docs (pestphp.com) for detailed patterns.

## Basic Usage

### Creating Tests

All tests are written using Pest. This is a library — there is no `php artisan make:test`. Create the file by hand under `tests/Feature/` or `tests/Unit/`, matching a sibling file: a `<?php` opener plus your `it()`/`test()` blocks (no class boilerplate). Feature tests use the `Webkinder\Sproutset\Tests\` namespace configured in `tests/Pest.php`.

### Test Organization

- Feature tests: `tests/Feature` — exercise the library through its public API (most tests go here).
- Unit tests: `tests/Unit` — isolated, pure logic only.
- Do NOT remove tests without approval.

### Basic Test Structure

Pest supports both `test()` and `it()` functions. Before writing new tests, check existing test files in the same directory to match the project's convention. Use `test()` if existing tests use `test()`, or `it()` if they use `it()`.

<!-- Basic Pest Test Example -->
```php
it('is true', function () {
    expect(true)->toBeTrue();
});
```

### Running Tests

- Run minimal tests with filter before finalizing: `vendor/bin/pest --filter=testName`.
- Run all tests: `composer test` (or `vendor/bin/pest`).
- Run file: `vendor/bin/pest tests/Feature/ExampleTest.php`.

## Assertions

Use specific assertions (`assertSuccessful()`, `assertNotFound()`) instead of `assertStatus()`:

<!-- Pest Response Assertion -->
```php
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
```

| Use | Instead of |
|-----|------------|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |

## Mocking

Import mock function before use: `use function Pest\Laravel\mock;`

## Datasets

Use datasets for repetitive tests (validation rules, etc.):

<!-- Pest Dataset Example -->
```php
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
```

## Pest 4 Features

| Feature | Purpose |
|---------|---------|
| Datasets | Table-driven tests over the same behavior |
| Test Sharding | Parallel CI runs |
| Architecture Testing | Enforce code conventions |

> Browser, smoke, and visual-regression testing don't apply here — the package has no HTTP routes or standalone pages. Its Blade image component is tested by rendering it directly (`Blade::render(...)` / Testbench component render) and asserting on the emitted markup; classes and helpers are tested directly.

### Test Sharding

Split tests across parallel processes for faster CI runs.

### Architecture Testing

Pest 4 includes architecture testing (from Pest 3):

<!-- Architecture Test Example -->
```php
arch('strict types')
    ->expect('Webkinder\Sproutset')
    ->toUseStrictTypes();
```

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Deleting tests without approval
- Reaching for a unit test where a feature test through the public API would be more meaningful
