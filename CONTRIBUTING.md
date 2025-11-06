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

2. **Branch Strategy** (Gitflow)
   - `main`: Production releases only
   - `develop`: Main development branch
   - `feature/*`: New features (branch from `develop`)
   - `hotfix/*`: Urgent fixes (branch from `main`)

   ```bash
   git checkout develop
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

```
Short summary (50 chars max)

Detailed explanation if needed. Focus on why, not how.
Fixes #123
```

- Use imperative mood ("Add feature" not "Added feature")
- Keep first line clear and concise

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

## Release Notes

CHANGELOG.md and GitHub release notes should be identical. Format:

```markdown
- <change message> by @<username> in #<PR number>
```

**Categories:**

- `Added`: New features
- `Fixed`: Bug fixes
- `Changed`: Changes in existing functionality
- `Removed`: Removed features

**Example:**

```markdown
- Add AVIF conversion support for PNG images by @johndoe in #123
- Fix srcset generation for responsive variants by @janedoe in #124
- Improve on-the-fly image size generation by @devuser in #125
```

## Merge Strategy

**Feature branches → develop:**

- Squash merge (keeps history clean)
- Delete branch after merge

**Release/Hotfix branches → main and develop:**

- Merge commit with `--no-ff`
- Delete branch after merge

---

We review PRs within a week. Ping us if you haven't received feedback.
