# Simdom

Simdom is PHP library for parsing and manipulating DOM documents.
It has no dependency and does not require `libxml` or `dom` extension.
It is designed to be as simple as possible.
Not a full DOM implementation, it is enough for most use cases.
Regular expressions are used extensively in the parsing logic, we are sorry for that.

## Features

- Type hinting is set for all classes / interfaces.
- Non-standard methods are added to `Document`, `DocumentFragment` and `Element` for convenience.
- `NodeType` and namespace URI constants are implemented as `Enum`.
- Properties `classList` and `children` of `Element` are lazy initialized.

## Major differences from DOM standard

- Handling of deprecated tags `frame`, `frameset`, and `plaintext` is not implemented.
  When encountered, they are treated as ordinary tags like `div`.
- `Attr` is not a `Node`.
- Parsing `<template>` will not create a `DocumentFragment` inside the `template` element.
  Its content will be treated as raw text.
- The DOM standard has a complicated logic of handling misaligned end tags.
  Here we try to find any matching start tag up to 3 levels, and discard the end tag if not found.
- Fixing of incorrect tag hierarchy e.g. `<li><ul></ul></li>` is not implemented.
