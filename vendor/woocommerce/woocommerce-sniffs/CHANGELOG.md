# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.2] - 2022-01-21
### Added
- Added `WooCommerce.Commenting.CommentHooksSniff`.

## [0.1.1] - 2021-08-29
### Changed
- Allow dependencies to use patch releases, no more pinned dependencies.

## [0.1.0] - 2020-08-06
### Added
- Added `WooCommerce.Functions.InternalInjectionMethod` sniff.
### Changed
- Renamed `WooCommerce-Core.Commenting.CommentTagsSniff` to `WooCommerce.Commenting.CommentTagsSniff`.
- Updated "wp-coding-standards/wpcs" to 2.3.0.
- Updated "dealerdirect/phpcodesniffer-composer-installer" to 0.7.0.

## [0.0.10] - 2020-04-07
### Changed
- Updated "wp-coding-standards/wpcs" to 2.2.1.
- Updated "dealerdirect/phpcodesniffer-composer-installer" to 0.6.2.

## [0.0.9] - 2019-11-11
### Changed
- Updated "wp-coding-standards/wpcs" to 2.2.0.

## [0.0.8] - 2019-10-16
### Changed
- Updated "wp-coding-standards/wpcs" to 2.1.1.
- Updated "phpcompatibility/phpcompatibility-wp" to 2.1.
### Added
- White flag `wc_make_phone_clickable()` on `WordPress.Security.EscapeOutput`.

## [0.0.7] - 2019-08-23
### Changed
- Updated "wp-coding-standards/wpcs" to 2.1.
### Added
- White flag `wc_query_string_form_fields()` on `WordPress.Security.EscapeOutput`.

## [0.0.6] - 2019-03-11
### Added
- White flag `wc_esc_json()` on `WordPress.Security.EscapeOutput`.

## [0.0.5] - 2018-11-20
### Changed
- Replaced "phpcompatibility/php-compatibility" to "phpcompatibility/phpcompatibility-wp".

## [0.0.4] - 2018-11-19
### Changed
- Updated "wp-coding-standards/wpcs" to version 1.2.

### Fixed
- Coding standards.

## [0.0.3] - 2018-11-06
### Added
- Included "phpcompatibility/php-compatibility" 9.0 as a dependency.
- Included "dealerdirect/phpcodesniffer-composer-installer" 0.5 as dependency.
- New custom message for `@category`.

### Changes
- Updated ruleset.xml with default validate and escape functions, also including default rules.

## [0.0.2] - 2018-03-22
### Added
- `@access` tag is prohibited now.

## 0.0.1 - 2017-12-21
### Added
- Initial code to throw warnings when using `@author`, `@category`, `@license` and `@copyright` tags.

[Unreleased]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.1.1...HEAD
[0.1.1]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.1.0...0.1.1
[0.1.0]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.10...0.1.0
[0.0.10]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.9...0.0.10
[0.0.9]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.8...0.0.9
[0.0.8]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.7...0.0.8
[0.0.7]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.6...0.0.7
[0.0.6]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.5...0.0.6
[0.0.5]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/woocommerce/woocommerce-sniffs/compare/0.0.1...0.0.2
