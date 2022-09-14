# Lever Changelog

## 1.0.4 - 2022-09-14
### Added
- Added `country` and `workplaceType` job properties. ([#2](https://github.com/workingconcept/lever-craft-plugin/issues/2))

## 1.0.3 - 2019-02-05
### Fixed
- Fixed a bug that might have incorrectly reported an error even though an application was submitted properly.

## 1.0.2 - 2019-01-10
### Changed
- Improved job application submissions to be more straightforward with error handling. Now works like [Contact Form](https://github.com/craftcms/contact-form), including AJAX support. See updated readme.

## 1.0.1 - 2019-01-10
### Added
- Added `EVENT_BEFORE_VALIDATE_APPLICATION` event so it's possible to make adjustments to an Application before it's validated and sent.

### Changed
- Refactored service to use getClient() rather than establishing on init().

## 1.0.0 - 2018-12-12
### Changed
- Removed `-beta` tag after testing.

## 1.0.0-beta.3 - 2018-12-12
### Changed
- Fixed model bug.

## 1.0.0-beta.2 - 2018-12-12
### Changed
- Updated Settings.

## 1.0.0-beta.1 - 2018-12-12
### Added
- Initial public release for Craft 3.
