# Changelog

## [0.3.0] - 2025-06-23

Complete rewrite of the library to use PHP 8.4 features. Please treat this as a new library.

## [0.2.1] - 2023-02-15

### Changed

- `Dom::createParser()` accepts boolean arguemnt to switch over using native PHP DOM extension for parsing.
- `Dom::createElement()` accepts thrid argument to conveniently inserting child nodes to the newly created element.
- `DOMTokenList`, `HTMLCollection`, `NamedNodeMap` and `NodeList` can be used by `count()` to get the number of items.

### Added

- New class `DomNodeConverter` to enable conversion between PHP `DOM-`objects and Simdom `Node` objects.

### Fixed

- The replaced `Attr` returned by `AttrList->setNamedItem()` should have its `ownerElement` set as null.



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
