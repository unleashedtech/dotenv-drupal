# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [Unreleased][unreleased]

## [0.2.0] - 2022-03-14

### Changed

- *Updated `Dotenv::getSettings` to prepend & append start/end chars to each pattern.*
- Updated `Dotenv::getSettings` to construct `trusted_host_patterns` based on `DOMAINS` if
  `TRUSTED_HOST_PATTERNS` environment variable is not set.
- Updated `SHIELD` variable to serve as the default username/password for the prompt if filled.

## [0.1.17] - 2022-01-27

### Fixed

- Removed additional "0" site in the array returned from `Dotenv::getSites`.

## [0.1.16] - 2022-01-26

### Fixed

- Resolved `MAILGUN_URL` API KEY "user" bug.

## [0.1.15] - 2022-01-25

### Changed

- Added support for `config.local.php`, `databases.local.php` & `settings.local.php` files.
- Added `MAILGUN_URL` support.

## [0.1.14] - 2022-01-21

### Changed

- Added `TRUSTED_HOST_PATTERNS` support.

## [0.1.13] - 2022-01-20

### Fixed

- Updated Dotenv::getConfig to indeed enable Shield by default.
- Updated Dotenv::getConfig to check for `SHIELD_USERNAME` instead of `SHIELD_USER`.

## [0.1.12] - 2022-01-20

### Changed

- Updating Dotenv::getConfig to enable Shield by default.

## [0.1.11] - 2022-01-07

### Changed

- Updating Dotenv::getConfig to support environments with full "production" name.
- Updating Dotenv::getDatabaseName to provide helpful output in Drush context for multi-site config with dis-allowed default site.

## [0.1.10] - 2022-01-04

### Changed

- Disallowed access to `default` site in a multi-site install by default.
- Added support for `$databases` variable alteration.
- Added support for `FILE_PUBLIC_PATH` environment variable.
- Added support for `FILE_PRIVATE_PATH` environment variable.
- Added support for `FILE_TEMP_PATH` environment variable.
- Added support for `CONFIG_SYNC_PATH` environment variable.

## [0.1.9] - 2021-12-29

### Fixed

- Fixed `Dotenv::getSettings` to use Drupal's standard-fare `development.services.yml` if a `dev` environment.

## [0.1.8] - 2021-12-14

### Fixed

- Fixed `Dotenv::getDatabaseName` method to use site name if no database specified in `DATABASE_URL`.

## [0.1.7] - 2021-12-01

### Changed

- Added more documentation concerning Drupal multi-site configuration.
- Added documentation for `DOMAINS` environment variable.
- Added documentation for `SITES` environment variable.
- Added Dotenv::getSites method to return Drupal-compatible list of sites.

### Fixed

- Inaccurate multi-site configuration examples in documentation.

## [0.1.6] - 2021-12-01

### Fixed

- Changed Dotenv::getPublicFilePath to return a relative path, as required by Drupal.

## [0.1.5] - 2021-11-30

### Changed

- Revising Dotenv::getConfig to support more custom Solr configurations.

## [0.1.4] - 2021-11-30

### Fixed

- Updating Dotenv to always use `$_SERVER` instead of `$_ENV`. See <https://github.com/drush-ops/drush/issues/4407#issuecomment-821232867>

## [0.1.3] - 2021-11-29

### Fixed

- Updating Dotenv::getSettings to define hash_salt if HASH_SALT environment variable is set.
- Revising Dotenv class methods to only use server VIRTUAL_HOST var if defined.

## [0.1.2] - 2021-11-29

### Fixed

- Updating Dotenv::getSettings to define generic settings based on default.settings.php.

## [0.1.1] - 2021-11-29

### Fixed

- Updating Dotenv::getSettings to load generic settings from default.settings.php if the file exists.

## [0.1.0] -

**Initial release!**

[unreleased]: https://github.com/unleashedtech/dotenv-drupal/compare/0.2.0...main
[0.2.0]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.17...0.2.0
[0.1.17]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.16...0.1.17
[0.1.16]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.15...0.1.16
[0.1.15]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.14...0.1.15
[0.1.14]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.13...0.1.14
[0.1.13]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.12...0.1.13
[0.1.12]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.11...0.1.12
[0.1.11]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.10...0.1.11
[0.1.10]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.9...0.1.10
[0.1.9]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.8...0.1.9
[0.1.8]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.7...0.1.8
[0.1.7]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.6...0.1.7
[0.1.6]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.5...0.1.6
[0.1.5]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/unleashedtech/dotenv-drupal/releases/tag/0.1.0
