# Contributing to Sproutset

Thanks for your interest in contributing! This project uses a simple trunk-based workflow with automated releases.

## Workflow

1. Fork the repository (or create a branch if you have write access).
2. Branch off `main`.
3. Make your changes.
4. Open a pull request targeting `main`.
5. Once approved, your PR is **squash-merged** into `main`.

## Commit messages

We use [Conventional Commits](https://www.conventionalcommits.org/). Your pull request title becomes the squash-commit message, so it must follow this format:

- `feat: ...` — a new feature
- `fix: ...` — a bug fix
- `refactor: ...` — a change that neither fixes a bug nor adds a feature
- `perf: ...` — a performance improvement
- `revert: ...` — revert a previous change

This matters: [Release Please](https://github.com/googleapis/release-please) reads these prefixes to generate the changelog and determine the next version.

## Releases

Releases are **fully automated**. Do not edit `CHANGELOG.md`, create tags, or publish releases by hand. Release Please maintains a release pull request; merging it cuts the version, updates the changelog, and creates the GitHub release.
