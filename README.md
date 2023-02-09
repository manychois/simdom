# Simdom

[![PHP version](https://badgen.net/packagist/php/manychois/simdom)](https://packagist.org/packages/manychois/simdom)
[![MIT license](https://badgen.net/github/license/manychois/simdom)](https://github.com/manychois/simdom/blob/main/LICENSE)
[![Codecov](https://codecov.io/github/manychois/simdom/branch/main/graph/badge.svg?token=gl07VPHIle)](https://codecov.io/github/manychois/simdom)
[![Github last commit](https://badgen.net/github/last-commit/manychois/simdom/main)](https://github.com/manychois/simdom/commits/main)
[![Packagist](https://badgen.net/packagist/v/manychois/simdom/latest)](https://packagist.org/packages/manychois/simdom)

Simdom is a lightweight PHP library designed to make parsing and manipulating DOM documents as straightforward as possible.<br>
This library requires no external dependencies or extensions - such as `libxml` or `dom`.<br>
Though not a full DOM implementation, for most use cases Simdom proves to be more than sufficient.<br>
Regular expressions are used extensively in the parsing logic. It is OK if you don't like this approach, we can't please everyone.

## Features

- Pretty print HTML5 document.
- Type hinting is placed everywhere.
- Remove meaningless properties (e.g. `childNodes`) and methods (e.g. `appendChild()`) from `Comment`, `DocumentType`, and `Text` for cleaner interface.
- Extra convenient methods are added to `Document`, `DocumentFragment` and `Element`, e.g. `dfs()` for depth-first search on desendant nodes.
- `NodeType` and namespace URI constants (`DomNs`) are implemented as `enum`.
- Throw exceptions with richer context when insertion or replacement of nodes will result in invalid HTML document.

## Getting Started

### Installation

Simdom requires **PHP 8.1** or later (as we use `enum` syntax).

To use this library in your project, run:
```bash
composer require manychois/simdom
```

## Major differences from DOM standard

- XML document will still be parsed as HTML5.
- Handling of deprecated tags `frame`, `frameset`, and `plaintext` is not implemented.
  When encountered, they are treated as ordinary tags like `div`.
- `Attr` does not inherit from `Node`, so will never participate in the DOM tree.
- Parsing `<template>` will not create a `DocumentFragment` inside the `template` element.
  Its content will be treated as raw text.
- The DOM standard has a complicated logic of handling misaligned end tags.
  Here we try to find any matching start tag up to 3 levels, and discard the end tag if not found.
- Fixing of incorrect tag hierarchy e.g. `<li><ul></ul></li>` is not implemented.

## Usage

### Parsing HTML

```php
$parser = \Manychois\Simdom\Dom::createParser();
$doc = $parser->parseFromString('<p>Hello, world!</p>');
// $doc is an instance of \Manychois\Simdom\Document
```

### Traversing and manipulating the DOM tree

```php
// Standard DOM methods for traversal and manipulation are available
$html = $doc->documentElement();
$body = $html->children()->item($html->children()->length() - 1);
$body->append(\Manychois\Simdom\Dom::createElement('div'));

// Simdom also provides extra convenient methods like dfs (Depth First Search)
foreach ($doc->dfs() as $node) {
    if ($node instanceof \Manychois\Simdom\Comment) {
        echo $node->data() . "\n";
    }
}
```

### Outputting HTML

```php
$option = new \Manychois\Simdom\PrettyPrintOption();
$option->indent ="\t";
echo \Manychois\Simdom\Dom::print($doc, $option);
```
