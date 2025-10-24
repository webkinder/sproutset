# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Added Sproutset service provider and service by @marcoluzi in #51
- Added rector to pre-commit formatting by @marcoluzi in #72
- Added image size management system with configuration by @marcoluzi in #73
- Added image size registration logic with srcset variant support by @marcoluzi in #73
- Added `show_in_ui` option to control image size visibility in WordPress media selector by @marcoluzi in #73
- Added post type filtering for conditional image size generation by @marcoluzi in #73
- Added `config/sproutset-image-sizes.php` configuration file by @marcoluzi in #73
- Added `roots/wordpress-no-content` (6.8.3) as dev dependency by @marcoluzi in #73
- Added `Image` Blade component (`<x-sproutset-image>`) for responsive image rendering with automatic srcset generation by @marcoluzi in #74
- Added automatic on-demand image size generation when missing sizes are requested by @marcoluzi in #74
- Added admin notice on WordPress media settings page to inform users about Sproutset configuration by @marcoluzi in #74
- Added `convert_to_avif` configuration option for automatic AVIF image conversion by @marcoluzi in #79
- Added automatic AVIF conversion for JPEG and PNG images via `image_editor_output_format` filter by @marcoluzi in #79

### Fixed

### Changed

- Changed pint to use parallel processing by @marcoluzi in #72
- Changed pint configuration by @marcoluzi in #72
- Changed `Sproutset` class to readonly with image management functionality by @marcoluzi in #73
- Changed `Sproutset` constructor to remove unused `$app` parameter by @marcoluzi in #74
- **BREAKING:** Changed configuration file from `sproutset-image-sizes.php` to `sproutset-config.php` by @marcoluzi in #79
- **BREAKING:** Changed config structure to nest image sizes under `image_sizes` key by @marcoluzi in #79
- Changed all config references from `sproutset-image-sizes` to `sproutset-config.image_sizes` by @marcoluzi in #79
- Changed service provider to publish `sproutset-config` instead of `sproutset-image-sizes` by @marcoluzi in #79
- Changed package license to GPL-3.0-or-later by @marcoluzi in #92

### Removed

## [0.1.0-alpha.3] - 2025-08-14

### Added

- Added Renovate configuration by @marcoluzi in #8
- Added composer schema definition to composer.json by @marcoluzi in #10
- Added credits file update checkbox to PR template by @marcoluzi in #11
- Added Node.js version definition to .node-version by @marcoluzi in #13
- Added pre-commit formatting and linting by @marcoluzi in #14

### Fixed

### Changed

### Removed

## [0.1.0-alpha.2] - 2025-05-09

### Added

- Added merge strategy guidelines for different branch types by @marcoluzi in #4
- Added release notes formatting guidelines by @marcoluzi in #4
- Add package metadata and license information to composer.json by @marcoluzi in #5

### Fixed

### Changed

### Removed

## [0.1.0-alpha.1] - 2025-05-09

### Added

- LICENSE.md
- CHANGELOG.md
- README.md
- composer.json and .gitignore
- CREDITS.md
- CODE_OF_CONDUCT.md
- CONTRIBUTING.md
- Issue templates for bug reports, feature requests, and questions
- Pull request template

### Fixed

### Changed

### Removed

[unreleased]: https://github.com/webkinder/sproutset/compare/v0.1.0-alpha.3...develop
[0.1.0-alpha.3]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-alpha.3
[0.1.0-alpha.2]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-alpha.2
[0.1.0-alpha.1]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-alpha.1
