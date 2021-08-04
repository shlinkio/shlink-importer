# CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## [2.3.1] - 2021-08-02
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* Dropped support for PHP 7.4

### Fixed
* *Nothing*


## [2.3.1] - 2021-08-02
### Added
* *Nothing*

### Changed
* [#34](https://github.com/shlinkio/shlink-importer/issues/34) Added experimental builds under PHP 8.1.
* [#36](https://github.com/shlinkio/shlink-importer/issues/36) Increased required phpstan level to 8.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#32](https://github.com/shlinkio/shlink-importer/issues/32) Fixed error when importing short URLs with no visits from another Shlink instance.


## [2.3.0] - 2021-05-22
### Added
* [#4](https://github.com/shlinkio/shlink-importer/issues/4) Added support to import from another Shlink instance through its REST API.

    It imports visits and metadata as well, preparing the implementation to support this on other import sources.

### Changed
* [#25](https://github.com/shlinkio/shlink-importer/issues/25) Increased required MSI to 80%.
* Updated to Infection 0.23

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [2.2.0] - 2021-02-06
### Added
* [#21](https://github.com/shlinkio/shlink-importer/issues/21) Added support to import URL `title` prop.
* [#5](https://github.com/shlinkio/shlink-importer/issues/5) Added support to import from a standard CSV file.

### Changed
* Migrated build to Github Actions.
* [#23](https://github.com/shlinkio/shlink-importer/issues/23) Increased required MSI to 75%.

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [2.1.0] - 2020-12-04
### Added
* Explicitly added PHP 8 as a supported version.

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*


## [2.0.1] - 2020-10-25
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* [#13](https://github.com/shlinkio/shlink-importer/issues/13) Ensured `shortCode` cannot be null on `ImportedShlinkUrl`.


## [2.0.0] - 2020-10-24
### Added
* *Nothing*

### Changed
* [#9](https://github.com/shlinkio/shlink-importer/issues/9) Now the `ImportedLinksProcessorInterface::process` method receives a `StyleInterface` instance as its first argument, allowing consumers to display the import progress and give feedback.
* [#10](https://github.com/shlinkio/shlink-importer/issues/10) The `ImportedShortUrl` model now wraps the source from which it was imported. Because of this, the source is no longer passed to the `ImportedLinksProcessorInterface::process` method.
* [#7](https://github.com/shlinkio/shlink-importer/issues/7) Increased required MSI to 85%.

### Deprecated
* *Nothing*

### Removed
* Removed `ShlinkUrl` deprecated class. Use `ImportedShlinkUrl` instead.

### Fixed
* *Nothing*


## [1.0.1] - 2020-10-24
### Added
* *Nothing*

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* Fixed `short-url:import` command throwing an exception if source is not provided.


## [1.0.0] - 2020-10-22
### Added
* [#2](https://github.com/shlinkio/shlink-importer/issues/2) Added support to import from bit.ly

### Changed
* *Nothing*

### Deprecated
* *Nothing*

### Removed
* *Nothing*

### Fixed
* *Nothing*
