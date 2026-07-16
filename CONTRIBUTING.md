# Contributing

Thank you for contributing to Sproutset! We welcome bug reports, feature requests, documentation improvements, and code contributions.

Please read our [Code of Conduct](CODE_OF_CONDUCT.md) before participating.

## Bug Reports

Search existing issues before submitting. Include:

- **Description**: What's happening vs. expected behavior
- **Steps to reproduce**: Detailed instructions
- **Environment**: OS, WordPress version, PHP version, Sproutset version
- **Visual evidence**: Screenshots or code snippets (if applicable)

## Security Vulnerabilities

Report security issues privately via:

1. **GitHub Security Advisory** (preferred): Repository → "Security" tab → "Advisories" → "New draft security advisory"
2. **Email**: [support@webkinder.ch](mailto:support@webkinder.ch)

**Do not** disclose security issues publicly until a fix is announced.

## First-Time Contributors

New to pull requests? Check out [First Timers Only](https://www.firsttimersonly.com/). Look for issues labeled [`good-first-issue`](https://github.com/webkinder/sproutset/issues?q=state%3Aopen%20label%3A%22good%20first%20issue%22).

## Development Workflow

1. **Setup**

   ```bash
   # Fork repo, then:
   git clone your-fork
   composer install
   ```

2. **Branch Strategy** (trunk-based)
   - `main`: the only long-lived branch; always releasable
   - `feature/*`: new features (branch from `main`)
   - `fix/*`: bug fixes, including urgent ones (branch from `main`)

   ```bash
   git checkout main
   git checkout -b feature/my-feature
   ```

3. **Code Standards**
   - Use strict types (`declare(strict_types=1);`)
   - Run formatters before committing:
     ```bash
     ./vendor/bin/pint     # Laravel Pint formatter
     ./vendor/bin/rector   # Rector refactoring
     ```
   - Pre-commit hooks run these automatically via `lint-staged`
   - Update documentation for changes
   - Use meaningful names for variables/methods

## Pull Requests

**Before submitting:**

- Update documentation
- Run code formatters
- Test your changes

**PR format:**

- Clear title describing changes
- Description of what and why
- Link related issues (#issue-number)
- Include screenshots for UI changes

**Commit messages:**

This project uses [Conventional Commits](https://www.conventionalcommits.org/). Releases are automated from commit history, so the prefix determines the version bump.

```
<type>: short summary (50 chars max)

Detailed explanation if needed. Focus on why, not how.
Fixes #123
```

- Types: `feat` (→ minor), `fix` (→ patch), `feat!` or a `BREAKING CHANGE:` footer (→ breaking), plus `docs`, `refactor`, `perf`, `chore`, `test`, `ci`.
- Because PRs are squash-merged, **the PR title must be a valid Conventional Commit** — it becomes the commit on `main` that drives the changelog.
- Use imperative mood ("add feature" not "added feature"); keep the first line clear and concise.

## Coding Style

**Principles:**

- Mandatory type hints
- Self-documenting code with descriptive names
- PHPDoc only when adding value beyond code
- Focused, concise methods

**Good (self-documenting):**

```php
public function registerEventHandler(string $eventName, callable $handler): void
{
    if (! $this->isValidEventName($eventName)) {
        throw new InvalidArgumentException("Invalid event name: {$eventName}");
    }

    $this->handlers[$eventName][] = $handler;
}
```

**Avoid (over-documented):**

```php
/**
 * Register a handler
 * @param string $event The event
 * @param callable $handler The handler
 * @return void
 */
public function register($event, $handler) { }
```

## Releases

Releases are automated by [Release Please](https://github.com/googleapis/release-please). You do **not** edit `CHANGELOG.md`, tag versions, or write release notes by hand.

As Conventional Commits land on `main`, Release Please maintains an open **"chore: release X.Y.Z"** pull request with the computed version bump and the updated changelog. Merging that PR tags the release and publishes the GitHub Release; Packagist picks up the new tag automatically.

- Version bumps follow the commit types (`feat` → minor, `fix` → patch, breaking → major). While the package is pre-`1.0`, breaking changes bump the minor and do not jump to `1.0.0`.
- To cut a specific version (e.g. the first stable release), add a `Release-As: 1.0.0` footer to a commit.

## Merge Strategy

All pull requests target `main` and are **squash-merged**:

- One squash commit per PR, using the (Conventional Commit) PR title as its message.
- Delete the branch after merge.

There are no release or hotfix branches — an urgent fix is a `fix/*` branch off `main` like any other change.

---

We review PRs within a week. Ping us if you haven't received feedback.
