# Sproutset

Modern responsive image management for projects using the Roots Acorn framework.

## Foundational Context

This is a composer library for the Roots Acorn framework (Laravel-flavored container/provider layer for WordPress) — not a standalone Laravel application. You are an expert with the packages & versions below. Abide by these specific versions.

Runtime dependencies:

    php - ^8.3 (CI matrix: 8.3, 8.4, 8.5)
    roots/acorn (ACORN) - ^6.2

Dev dependencies (tooling, not shipped):

    pestphp/pest (PEST) - ^4.7
    larastan/larastan (LARASTAN) - ^3.10
    laravel/pint (PINT) - ^1.29
    rector/rector (RECTOR) - ^2.5
    driftingly/rector-laravel - ^2.5
    orchestra/testbench (TESTBENCH) - ^11.1

There is no application here — no `artisan` binary, no models, migrations, controllers, routes, or Eloquent. Laravel enters only transitively through `orchestra/testbench`, which boots a throwaway app so the suite can exercise the library. Package code ships under `src/` (namespace `Webkinder\Sproutset\`); tests live under `tests/` (namespace `Webkinder\Sproutset\Tests\`).

## Skills Activation

This project has domain-specific skills in **`.claude/skills/`**. Activate the relevant skill whenever you work in that domain — don't wait until you're stuck.

- **php-best-practices** — writing/reviewing PHP: style, security, error handling, architecture.
- **pest-testing** — writing or fixing any Pest test.
- **blaze-optimize** — only if optimizing Blade component rendering.

## Conventions

- You must follow all existing code conventions used in this library. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, isRegisteredForDiscounts, not discount().
- Check for existing classes/helpers to reuse before writing a new one.

## Front-End Boot Safety

- The service provider boots on **every** WordPress request, front-end included. Do not call admin-only WordPress functions (e.g. `wp_tempnam`) on the boot path. A library must never fatal a request.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Library Structure & Architecture

- Stick to the existing directory structure; don't create new base folders without approval.
- Do not change the library's dependencies without approval.
- Public API surface is deliberate: adding a new public class, method, or config key is a design decision — check `specs/` first (see below).

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: public function __construct(public GitHub $github) { }. Do not leave empty zero-parameter __construct() methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: function isAccessible(User $user, ?string $path = null): bool
- Use TitleCase for Enum keys: FavoritePerson, BestLake, Monthly.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

## Specs

- Before implementing any non-trivial feature or behavior change, check `specs/` for a matching spec and treat it as the source of truth. If none exists, write or propose one first from `specs/_template.md`.
- Keep specs and their Pest tests in sync: every Gherkin scenario maps to a named test, and behavior changes edit the spec before the code.
- See `specs/README.md` for the convention.

## Testing

- This library uses **Pest**, running under **Testbench** (which supplies the Laravel/Acorn app context). There is no `php artisan` in this repo — create test files by hand under `tests/Feature` or `tests/Unit`, matching the sibling files and the `Webkinder\Sproutset\Tests\` namespace.
- Run the suite with `composer test` (→ `vendor/bin/pest`). Filter to what you touched: `vendor/bin/pest --filter=testName`.
- Most tests should be **feature** tests exercising the library through its public API. Reach for a unit test only for isolated, pure logic.
- Do NOT delete tests without approval.

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure quality and speed — filter to the file or test you changed.

## Quality Gate

Composer scripts wrap the toolchain; CI runs the same commands (see `.github/workflows/ci.yml`).

- `composer lint` — Rector then Pint (writes fixes).
- `composer lint:check` — Rector `--dry-run` + Pint `--test` (no writes; what CI checks).
- `composer types:check` — PHPStan/Larastan static analysis.
- `composer test` — Pest.
- `composer check` — the full gate: `lint:check` + `types:check` + `test`.

After modifying any PHP file, before finalizing:

1. Run `vendor/bin/pint --dirty` to format only your changes (do not run `--test`; just let it fix).
2. Run `composer types:check` and fix any PHPStan findings your change introduced.
3. Run the affected Pest tests.
