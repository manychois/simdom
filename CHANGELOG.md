# Changelog

## [0.2.0] - 2023-02-12

### Changed

- Rewrite the library so it requires lower PHP version 7.4 (before 8.1).
- Rewind `phpunit/phpunit` from 10.0 to 9.0.
- Convert `DomNs` from `enum` to class constants.

### Removed

- `NodeType` is removed and constants are moved under `Node`.



## [0.1.1] - 2023-02-07

### Changed

- Rewrite the pretty print DOM node function.
- Improve test coverage to over 95%.
- Bump `phpunit/phpunit` from 9.5.28 to 10.0.4.
- Rename `PrintOption` to `PrettyPrintOption`.

### Added

- Implement mixins e.g. `NonDocumentTypeChildNodeMixin` in the form of `trait`'s for code reuse.

### Fixed

- Fix many minor bugs found during test coverage improvement.



## [0.1.0] - 2023-01-30

First release of the library.
