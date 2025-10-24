# Contributing

## Introduction

First off, thank you for considering contributing to Sproutset. It's people like you that make Sproutset such a great tool.

Sproutset is an open source project and we welcome contributions from our community. There are many ways to contribute, from improving the documentation, submitting bug reports and feature requests or writing code.

## Code of Conduct

We expect all contributors to adhere to our [Code of Conduct](CODE_OF_CONDUCT.md). Please read it before participating.

## Bug Reports

If you discover a bug in Sproutset, please file a bug report using our issue template. Before submitting, please search existing issues to avoid duplicates.

A good bug report should include:

1. **Bug Description**

   - What is happening?
   - What did you expect to happen?
   - Any relevant error messages

2. **Steps to Reproduce**

   - Detailed step-by-step instructions
   - Example:
     1. Go to '...'
     2. Click on '...'
     3. See error

3. **Visual Evidence** (if applicable)

   - Screenshots
   - Screen recordings
   - Code snippets

4. **Environment Information**
   - Device (e.g., MacBook Pro)
   - OS and version
   - Browser and version
   - WordPress version
   - PHP version
   - Sproutset version

The goal of a bug report is to make it easy for yourself - and others - to replicate and fix the issue. The more details you provide, the faster we can help resolve the problem.

## Security Vulnerabilities

If you discover a security vulnerability, you have two options to report it privately:

### 1. GitHub Security Advisory (Preferred)

1. Navigate to the repository's Security tab
2. Click 'Report a vulnerability'
3. Fill in the advisory form with as much detail as possible

For more information about this process, see [GitHub's documentation on private vulnerability reporting](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing/privately-reporting-a-security-vulnerability).

### 2. Email

Alternatively, you can send a detailed report to [support@webkinder.ch](mailto:support@webkinder.ch).

Please **do not** disclose security-related issues publicly until a fix has been announced. We appreciate your efforts to responsibly disclose your findings.

## Your First Contribution

Working on your first Pull Request? You can learn how from this free guide: [First Timers Only](https://www.firsttimersonly.com/). It's a great resource that walks you through the process step by step.

To help newcomers get started, we've labeled some issues as `good-first-issue`. These are issues that have been identified as good entry points for new contributors. You can find them [here](https://github.com/webkinder/sproutset/issues?q=state%3Aopen%20label%3A%22good%20first%20issue%22).

## Development Workflow

1. **Fork and clone the repository**

   - Fork the repo on GitHub
   - Clone your fork locally

2. **Set up the development environment**

   ```bash
   composer install
   ```

3. **Choose the right branch**
   We follow the [Gitflow Workflow](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow). Here's how to choose the right branch:

   - `main` - Production releases only
   - `develop` - Main development branch
   - `feature/*` - For new features (branch from `develop`)
   - `hotfix/*` - For urgent fixes (branch from `main`)
   - `release/x.y.z` - For final release preparations

   Examples:

   ```bash
   # For a new feature
   git checkout develop
   git checkout -b feature/my-new-feature

   # For a hotfix
   git checkout main
   git checkout -b hotfix/critical-fix
   ```

4. **Write your code**

   Follow our coding standards and best practices:

   **PHP Code**
   - We use [Laravel Pint](https://laravel.com/docs/pint) for code formatting (Laravel preset with custom rules)
   - [Rector](https://getrector.com/) handles automated refactoring and code quality improvements
   - Code must use strict types (`declare(strict_types=1);`)
   - Follow Laravel conventions and best practices
   - See `pint.json` and `rector.php` for detailed configuration

   **Before Committing**
   ```bash
   # Format PHP code
   ./vendor/bin/pint

   # Run Rector refactoring
   ./vendor/bin/rector
   ```

   **Note:** Pre-commit hooks automatically run these tools via `lint-staged`, but it's good practice to run them manually during development.

   **Additional Guidelines**
   - Update documentation for any changes
   - Use meaningful variable and method names
   - Add PHPDoc blocks for public methods (see [Coding Style](#coding-style) section)

## Pull Request Guidelines

1. **Before Submitting**

   - Update relevant documentation
   - Add or update PHPDoc blocks
   - Check coding style compliance

2. **Pull Request Format**

   - Clear title describing the changes
   - Detailed description of what was changed and why
   - Link related issues using #issue-number
   - Include screenshots for UI changes

3. **Commit Messages**
   Follow these guidelines for clear and descriptive commits:

   ```
   Short summary of changes (50 chars or less)

   More detailed explanatory text, if necessary. Wrap it to about 72
   characters. Explain the problem that this commit is solving. Focus
   on why you made the change as opposed to how (the code explains that).

   If the change fixes a specific issue, reference it here.
   Fixes #123
   ```

   Tips for good commit messages:

   - Use the imperative mood ("Add feature" not "Added feature")
   - Keep the first line concise and clear
   - Separate subject from body with a blank line
   - Use the body to explain what and why vs. how

## Coding Style

### Self-Documenting Code

Write code that documents itself through clear naming and type hints:

**Prefer Clear Naming Over Documentation**
- Use descriptive method, function, and variable names that clearly express their purpose
- The code should be readable without needing comments to explain what it does
- Type hints are mandatory. They serve as inline documentation.

**When to Use PHPDoc Blocks**

Only add PHPDoc blocks when they provide value beyond what the code already expresses:
- Complex business logic that needs explanation
- Non-obvious behavior or side effects
- Public API methods in packages
- When `@throws` documents exceptions that aren't obvious from the code

**Example: Good (Self-Documenting)**
```php
public function registerEventHandler(string $eventName, callable $handler): void
{
    if (! $this->isValidEventName($eventName)) {
        throw new InvalidArgumentException("Invalid event name: {$eventName}");
    }
    
    $this->handlers[$eventName][] = $handler;
}
```

**Example: Avoid (Over-Documented)**
```php
/**
 * Register a handler
 *
 * @param string $event The event
 * @param callable $handler The handler
 * @return void
 */
public function register($event, $handler)
{
    // ...
}
```

### Code Style

- Type hints are mandatory
- Use meaningful and descriptive names for variables, methods, and classes
- Keep methods focused and concise
- Add appropriate spacing for readability
- Prefer self-documenting code over comments

## Documentation

When adding or updating documentation:

- Use clear, concise language
- Include code examples for features
- Use proper markdown formatting
- Add PHPDoc blocks for all public methods
- Update both inline and external documentation

## Release Notes Guidelines

Both CHANGELOG.md and GitHub release notes should be identical and follow this format. When creating a release, copy the relevant section from CHANGELOG.md into the GitHub release notes to maintain consistency.

### Format

- Each change should be documented as:
  ```
  - <change message> by @<username> in #<PR number>
  ```

### Examples

```markdown
- Add dark mode support by @johndoe in #123
- Fix mobile navigation bug by @janedoe in #124
- Update documentation for API endpoints by @devuser in #125
```

### Categories

Group changes under these categories:

- `Added` for new features
- `Fixed` for any bug fixes
- `Changed` for changes in existing functionality
- `Removed` for now removed features

## Merge Strategy Guidelines

We use different merge strategies depending on the branch type to maintain a clean and meaningful git history.

### Feature Branches → Develop

**Always use squash merge**

- Combines all feature commits into a single commit on `develop`
- Keeps the `develop` history clean and linear
- Each feature appears as one logical unit
- Delete the feature branch after merging

### Release Branches → Main and Develop

**Always use merge commit with `--no-ff`**

- Creates a merge commit that marks the release point
- Preserves the release branch structure in history
- Both `main` and `develop` get identical merge commits
- Delete the release branch after merging to both branches

### Hotfix Branches → Main and Develop

**Always use merge commit with `--no-ff`**

- Same strategy as release branches
- Marks the hotfix clearly in history
- Ensures both branches receive the fix

---

We aim to review pull requests within a week. Feel free to ping the team if you haven't received feedback after that time.
