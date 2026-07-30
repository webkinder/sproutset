---
name: php-best-practices
description: "Apply when writing, reviewing, or refactoring PHP in this Acorn/WordPress library — service providers, service classes, value objects, helpers, and their tests. Covers coding style and naming, security (input/output handling, no raw SQL, safe file handling), error handling, and architecture (single-purpose classes, dependency injection). Use for PHP code reviews and refactoring toward Laravel-flavored conventions. Not for controllers/models/migrations/Eloquent — this package has none."
license: MIT
---

# PHP Best Practices (Acorn library)

Best practices for the PHP in this library, prioritized by impact. Each rule teaches what to do and why. This is a Composer library for the Roots Acorn framework (a Laravel-flavored container/provider layer for WordPress) — not a full Laravel app. Rules about controllers, models, migrations, Eloquent, queues, mail, routing, and scheduling have been removed because this package has none of that surface.

## Consistency First

Before applying any rule, check what the codebase already does. The best choice is the one the surrounding code already uses, even if another pattern would be theoretically better. Inconsistency is worse than a suboptimal pattern.

Check sibling files and related classes or tests for established patterns. If one exists, follow it — don't introduce a second way. These rules are defaults for when no pattern exists yet, not overrides.

## Quick Reference

### 1. Conventions & Style → `rules/style.md`

- Follow Laravel/Acorn naming conventions for all entities
- Prefer helpers (`Str`, `Arr`, `Number`, `Uri`, `Str::of()`) over raw PHP functions where they read more clearly
- No JS/CSS in Blade, no HTML in PHP classes
- Code should be readable; comments only where they earn their place

### 2. Security → `rules/security.md`

- No raw SQL with user input — use the query builder or WordPress `$wpdb->prepare()`
- Escape all output (`{{ }}` in Blade, WordPress `esc_*` in template context)
- Validate MIME type, extension, and size for any file handling
- Never commit secrets; read config through `config()`, never inline

### 3. Error Handling → `rules/error-handling.md`

- Throw typed exceptions with structured context; let the host app decide how to report
- `ShouldntReport` for exceptions that should never log
- Fail safely on the front-end boot path — a library must not fatal a request (see the front-end boot-safety memory)

### 4. Architecture → `rules/architecture.md`

- Single-purpose classes; dependency injection over the `app()` helper
- Follow Acorn/Laravel conventions; don't override framework defaults
- `mb_*` for UTF-8 safety
- Keep the provider's `register()`/`boot()` lean and side-effect-safe

### 5. Testing Patterns → `rules/testing.md`

- Favor factories, states, and sequences over manual setup where models exist
- Use fakes (`Event::fake()`, etc.) after setup, not before
- Most tests are feature tests running under Testbench

> Testing mechanics (Pest syntax, running the suite) live in the **pest-testing** skill — this section is about testing *judgment*.

## How to Apply

1. Identify the file type and select relevant sections (e.g., a service provider → §2, §3, §4; a helper → §1, §2).
2. Check sibling files for existing patterns — follow those first per Consistency First.
3. When a rule references a Laravel-app feature this package doesn't have, skip it.
