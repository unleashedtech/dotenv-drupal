# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [Unreleased][unreleased]

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

[unreleased]: https://github.com/unleashedtech/dotenv-drupal/compare/0.1.3...main
[0.1.3]: https://github.com/unleashedtech/dotenv-drupal/releases/tag/v0.1.3
[0.1.2]: https://github.com/unleashedtech/dotenv-drupal/releases/tag/v0.1.2
[0.1.1]: https://github.com/unleashedtech/dotenv-drupal/releases/tag/v0.1.1
[0.1.0]: https://github.com/unleashedtech/dotenv-drupal/releases/tag/v0.1.0
