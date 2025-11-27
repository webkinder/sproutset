# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Added focal point cropping support and configuration (`focal_point_cropping`, `max_on_demand_generations_per_request`) powered by `spatie/image`, including focal-aware rendering in the `Image` component by @marcoluzi in #129
- Added WordPress media focal point UI (drag handle + X/Y fields), updated translations, and `sproutset:reapply-focal-crop` CLI command to reapply focal crops (optionally with optimization) by @marcoluzi in #129

### Fixed

### Changed

- Changed cron-based optimization and focal recropping to use the shared `CronScheduler` helper and updated `CronOptimizer` logic by @marcoluzi in #129

### Removed

## [v0.1.0-beta.3] - 2025-11-24

### Added

- Added `Release` and `Other` types to PR template by @marcoluzi in #112
- Added logic to handle SVG attachments by @marcoluzi in #116
- Added helper class for normalizing image size configurations by @marcoluzi in #118
- Added `normalizeImageSourceData` method to `Image` component by @marcoluzi in #118
- Added configurable image size synchronization strategy and `sproutset:sync-image-sizes` CLI command by @marcoluzi in #119
- Added check for image file type before rendering component by @marcoluzi in #121

### Fixed

### Changed

- Updated contributing guidelines to better explain merge strategies by @marcoluzi in #112
- Updated PR template to include changes to merge strategy by @marcoluzi in #112
- Changed WordPress version requirement to ^5.9 || ^6.0 by @marcoluzi in #120
- Refined image size option synchronization to use a `SyncStrategy` enum and avoid unnecessary work on frontend requests by @marcoluzi in #119

### Removed

## [v0.1.0-beta.2]  - 2025-11-06

### Added

- Added validation for required WordPress image sizes (`thumbnail`, `medium`, `medium_large`, `large`) in configuration by @marcoluzi in #88
- Added automatic synchronization of image size configurations from config file to WordPress database options by @marcoluzi in #88
- Added readonly/disabled styling and attributes to media settings fields in WordPress admin by @marcoluzi in #88
- Added in-request caching to the `Image` component to boost performance by preventing redundant processing for identical images on the same page load by @marcoluzi in #102
- Added `ConfigurationValidator` manager class for config validation by @marcoluzi in #104
- Added `TextDomainManager` manager class for i18n text domain loading by @marcoluzi in #104
- Added `ImageSizeManager` manager class for image size registration and filtering by @marcoluzi in #104
- Added `AdminNotificationManager` manager class for admin notices and UI by @marcoluzi in #104
- Added `OptimizationManager` manager class for optimization features by @marcoluzi in #104
- Added automatic `object-fit: cover` inline style when images are smaller than configured dimensions by @marcoluzi in #104

### Fixed

- Fixed dimension detection to prioritize configured dimensions over actual image dimensions by @marcoluzi in #104
- Fixed responsive image behavior to prevent stretching on small screens by @marcoluzi in #104
- Fixed `sizes` attribute generation to use actual image width for optimal browser selection by @marcoluzi in #104
- Fixed an issue where WP Media Folder plugin threw errors due to missing core image size labels by @marcoluzi in #108

### Changed

- Changed conditional logic in `Image` component to use `in_array()` for better readability by @marcoluzi in #88
- Changed Node.js version to 24 by @renovate[bot] in #98
- Changed README.md to update image component syntax in documentation by @marcoluzi in #103
- Changed the `Image` component by refactoring its internal logic into smaller, single-responsibility methods, improving maintainability and adherence to SOLID principles by @marcoluzi in #102
- Changed the `Image` component to more accurately calculate aspect ratios and dimensions, ensuring images never exceed their original size and correctly handle crop/non-crop settings by @marcoluzi in #102
- Changed `Sproutset` class architecture to use focused manager classes following SOLID principles by @marcoluzi in #104
- Changed all code to follow self-documenting principles with descriptive naming by @marcoluzi in #104
- Changed PHPDoc usage to only include when adding value beyond code by @marcoluzi in #104
- Changed README.md structure by @marcoluzi in #104
- Changed CONTRIBUTING.md structure by @marcoluzi in #104

## [v0.1.0-beta.1] - 2025-10-24

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
- Added `auto_optimize_images` configuration option to control automatic image optimization by @marcoluzi in #86
- Added `sproutset:optimize` CLI command for manual image optimization with progress tracking by @marcoluzi in #86
- Added `ImageOptimizer` service for optimizing images using multiple optimization binaries by @marcoluzi in #86
- Added `CronOptimizer` service for background image optimization via WordPress cron by @marcoluzi in #86
- Added automatic image optimization on upload when `auto_optimize_images` is enabled by @marcoluzi in #86
- Added automatic optimization for on-the-fly generated image sizes by @marcoluzi in #86
- Added admin notice for missing optimization binaries in development/staging environments by @marcoluzi in #86
- Added optimization status tracking in attachment metadata with hash verification by @marcoluzi in #86
- Added i18n support with translations for DE, FR and IT locales by @marcoluzi in #87

### Fixed

- Fix PHP 8.2 compatibility by removing typed class constants in CronOptimizer by @marcoluzi in #93

### Changed

- Changed Rector configuration to automatically remove unused imports by @marcoluzi in #89
- Changed Rector configuration to remove `strictBooleans` rule from preparation rules by @marcoluzi in #89
- Changed pint to use parallel processing by @marcoluzi in #72
- Changed pint configuration by @marcoluzi in #72
- Changed `Sproutset` class to readonly with image management functionality by @marcoluzi in #73
- Changed `Sproutset` constructor to remove unused `$app` parameter by @marcoluzi in #74
- **BREAKING:** Changed configuration file from `sproutset-image-sizes.php` to `sproutset-config.php` by @marcoluzi in #79
- **BREAKING:** Changed config structure to nest image sizes under `image_sizes` key by @marcoluzi in #79
- Changed all config references from `sproutset-image-sizes` to `sproutset-config.image_sizes` by @marcoluzi in #79
- Changed service provider to publish `sproutset-config` instead of `sproutset-image-sizes` by @marcoluzi in #79
- Changed package license to GPL-3.0-or-later by @marcoluzi in #92
- Changed PHP version requirement from `~8.4.0` to `^8.2` in composer.json by @marcoluzi in #93
- Changed `roots/wordpress-no-content` from dev dependency to regular dependency by @marcoluzi in #93
- Changed issue templates to remove emojis and simplify formatting by @marcoluzi in #93
- Changed CONTRIBUTING.md to update coding guidelines and remove outdated references by @marcoluzi in #93
- Changed README.md with comprehensive documentation including installation, configuration, and usage examples by @marcoluzi in #93

### Removed

- Remove ESLint configuration and dependencies by @marcoluzi in #93
- Remove Prettier Blade plugin by @marcoluzi in #93
- Remove `.blade.format.json` configuration file by @marcoluzi in #93
- Remove `.prettierrc.json` configuration file by @marcoluzi in #93
- Remove `eslint.config.mjs` configuration file by @marcoluzi in #93
- Remove `translate:js` npm script by @marcoluzi in #93
- Remove Blade-specific formatting from lint-staged configuration by @marcoluzi in #93

## [v0.1.0-alpha.3] - 2025-08-14

### Added

- Added Renovate configuration by @marcoluzi in #8
- Added composer schema definition to composer.json by @marcoluzi in #10
- Added credits file update checkbox to PR template by @marcoluzi in #11
- Added Node.js version definition to .node-version by @marcoluzi in #13
- Added pre-commit formatting and linting by @marcoluzi in #14

## [v0.1.0-alpha.2] - 2025-05-09

### Added

- Added merge strategy guidelines for different branch types by @marcoluzi in #4
- Added release notes formatting guidelines by @marcoluzi in #4
- Add package metadata and license information to composer.json by @marcoluzi in #5

## [v0.1.0-alpha.1] - 2025-05-09

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

[unreleased]: https://github.com/webkinder/sproutset/compare/v0.1.0-beta.3...develop

[v0.1.0-beta.3]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-beta.3
[v0.1.0-beta.2]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-beta.2
[v0.1.0-beta.1]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-beta.1
[v0.1.0-alpha.3]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-alpha.3
[v0.1.0-alpha.2]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-alpha.2
[v0.1.0-alpha.1]: https://github.com/webkinder/sproutset/releases/tag/v0.1.0-alpha.1
