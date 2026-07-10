# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased]

## [1.1.3] - 2026-07-10

### Fixed

- Preserve the modified date on the legacy-metabox follow-up save. The block editor fires this save as a separate request where the in-memory silent-update flag is unset, so `post_modified` was still bumped on sites with classic metaboxes; a short-lived, per-post/per-user marker now carries the silent-update state across both requests.

### Added

- `languages/outstand-silent-update.pot` translation template.
- PHPUnit integration test suite (`@wordpress/env` + `phpunit`) covering both silent-save request paths.

### Changed

- Guard the update-checker bootstrap with a `class_exists()` check.

## [1.1.2] - 2026-06-29

### Fixed

- Abort early when the Composer autoloader is missing instead of triggering a fatal error.
- Sync the `OUTSTAND_SILENT_UPDATE_VERSION` constant with the plugin version.

## [1.1.1] - 2026-05-08

- Added automated GitHub Release packaging via reusable release workflow; installation now points to the latest release ZIP.

## [1.1.0] - 2026-03-28

### Changed

- Update GitHub repository URL.

## [1.0.0] - 2026-03-27

- Initial release.
