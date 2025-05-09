# Contributing

## Introduction

First off, thank you for considering contributing to Sproutset. It's people like you that make Sproutset such a great tool.

Sproutset is an open source project and we welcome contributions from our community. There are many ways to contribute, from writing tutorials or blog posts, improving the documentation, submitting bug reports and feature requests or writing code.

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

To help newcomers get started, we've labeled some issues as `type:good-first-issue`. These are issues that have been identified as good entry points for new contributors. You can find them [here](https://github.com/webkinder/sproutset/labels/type%3Agood-first-issue).

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

TODO
4. **TODO: Write your code**
   - Follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
   - Add tests for new functionality
   - Update documentation for any changes

TODO
5. **Test your changes**
   ```bash
   composer test      # Run PHPUnit tests
   composer phpcs     # Check coding standards
   composer phpstan   # Static analysis
   ```

## Pull Request Guidelines

1. **Before Submitting**
   - Ensure tests are passing
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

TODO
## Coding Style

### PHPDoc Blocks

Use the following format for PHPDoc blocks:

```php
/**
 * Register a new handler for the given event.
 *
 * @param  string  $event
 * @param  callable|string  $handler
 * @return void
 *
 * @throws \InvalidArgumentException
 */
public function register(string $event, $handler)
{
    // ...
}
```

### Code Style

- Follow PSR-12 coding standard
- Use type hints when possible
- Add appropriate spacing
- Use meaningful variable names
- Keep methods focused and concise

## Documentation

When adding or updating documentation:

- Use clear, concise language
- Include code examples for features
- Use proper markdown formatting
- Add PHPDoc blocks for all public methods
- Update both inline and external documentation

## Merge Strategy Guidelines

We follow specific merge strategies for different types of branches to maintain a clean and meaningful history:

### Feature Branches → Develop
- Use squash merges or rebase merges
- This keeps the develop history clean and readable
- Example: `feature/foo → develop` (squash or rebase merge)

### Release/Hotfix Branches → Main and Develop
- Use merge commits with `--no-ff` flag
- This preserves the meaningful branch structure
- Marks release points clearly in history
- Example: 
  ```
  release/1.0.0 → main (merge commit)
  release/1.0.0 → develop (merge commit)
  ```

### Benefits of this Strategy
- `develop` maintains a readable line of squashed features
- `main` contains only release merge commits
- Both `main` and `develop` share identical release points
- No "ahead/behind" branch indicators for releases
- History remains clear, auditable, and easy to follow

We aim to review pull requests within a week. Feel free to ping the team if you haven't received feedback after that time.