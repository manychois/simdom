# Changelog

## [0.3.1] - 2025-09-21

### Fixed

- Improve unit test coverage.

### Added

- `AbstractParentNode`: add properties `firstChild` and `lastChild`.
- New class `PrettyPrinter` to generate well-indented HTML.
- New functions `append()`, `e()` and `parseElement()` under namespace `Manychois\Simdom` to help construct HTML.

### Fixed

- `AbstractNode->$previousElementSibling`: fix infinite loop bug.

## [0.3.0] - 2025-09-02

Complete rewrite of the library to use PHP 8.4 features. Please treat this as a new library.
