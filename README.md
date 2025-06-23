# Simdom - A simplified and relaxed DOM structure library

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-777bb3.svg)](https://www.php.net/releases/8.4/en.php)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Simdom is a lightweight PHP library designed to make parsing and manipulating DOM documents as straightforward as possible. It requires no external dependencies or extensions.

Without using the built-in PHP DOM extension, Simdom can have its own opinionated appraoch on how HTML documents should be parsed and manipulated. It lets you to work with "invalid" HTML structure, then you can fix it in your own way.

Before outputing the HTML string of the document, you can call the `$document->validate()` method to ensure that the document is valid according to the HTML5 specification.

## Key differences from the standard HTML5 DOM specification / PHP DOM extension

### Simplified node types

Simdom provides 6 node types that form the DOM tree:

- `Document` - The root document node
- `Doctype` - Document type declarations
- `Element` - HTML elements with attributes and child nodes
- `Text` - Text content within elements
- `Comment` - HTML comments
- `Fragment` - Document fragments for grouping nodes

Attributes are not considered as a node type in Simdom, but rather as properties of `Element` nodes.

CDATA section or processing instructions are not supported, as they would not be valid in HTML5 documents.

## Simplified and relaxed DOM structure

- There is no concept of an owner document, meaning nodes can be freely moved between documents.
- There is no concept of namespace.
- `Document`, `Element` and `Fragment` nodes can have child nodes of any type except `Document` and `Fragment`, in any order, i.e.:
  - `Document` can hold `Text` child.
  - `Document` does not restrict at most one `Doctype` child, and it does not have to be placed before any `Element` child.
  - `Document` does not restrict at most one `Element` child.
  - `Element` and `Fragment` can hold `Doctype` child.
- There is no concept of valid element structure, meaning elements can be nested in any way, even if it would not be valid HTML5, i.e. `<table><ul></ul></table>` would be parsed as it is.
- Misaligned end tags are fixed by finding the last matching start tag, i.e. `<div><span>abc</div>` would be parsed as `<div><span>abc</span></div>`. If there is no matching start tag, the end tag is ignored.
- `<template>` elements are treated as a Rawtext type element like `<script>` or `<style>`.
- Self-closing tag syntax is supported, for example `<div />` is parsed as `<div></div>`.
- All element names and attributes names are parsed as their ASCII-lowercase form.

## Restrictions

However, there are still some lines you cannot cross in Simdom:
- `Document` and `Fragment` has no parent node, and cannot be a child of any other node. (Inserting `Fragment` as a child of any parent node is fine though, as it means inserting the `Fragment`'s child nodes.)
- `Element` name and attribute names must conform to the HTML5 specification.
- `Doctype` name, public identifier and system identifier must conform to the HTML5 specification.
- `Doctype` name must be present if either public or system identifier is present.
- No control characters are allowed anywhere, e.g. you cannot inject an delete character (U+007F) to a `Text` node.
- `Comment` cannot contain the character sequence `-->`.
- If a `Text` node is under a Rawtext type (e.g. `<script>`) or Rcdata type (e.g. `<textarea>`) element, it cannot contain the character sequence which may terminate the corresponding element start tag, e.g. `</script`, or `</textarea`.


## Getting Started

### Installation

```bash
composer require manychois/simdom
```

## Requirements

- PHP 8.4 or higher

## Some Basic Usages

### Parsing HTML Documents

```php
use Manychois\Simdom\HtmlParser;

$parser = new HtmlParser();
$doc = $parser->parseDocument('<!DOCTYPE html><html><body><p>Hello, world!</p></body></html>');
// $doc is an instance of \Manychois\Simdom\Document
```

### Node Manipulation

```php
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;

// Create documents
$doc = Document::create();

// Create elements
$div = Element::create('div');
$div->setAttr('class', 'container');
$div->id = 'main-content';
```

### Traversing and Manipulating the DOM Tree

```php
// Access document parts
$html = $doc->documentElement; // The <html> element
$head = $doc->head;           // The <head> element
$body = $doc->body;           // The <body> element

// Navigate the tree
$element = $body->firstElementChild;
$nextElement = $element->nextElementSibling;
$parent = $element->parent;

// Child node access
foreach ($body->childNodes as $node) {
    echo get_class($node) . "\n";
}

// Element-only access
foreach ($body->children as $element) {
    echo $element->name . "\n";
}
```

### Adding and Removing Nodes

```php
// Append nodes
$body->append($div, $text);
$body->appendChild($comment);

// Prepend nodes
$body->prepend(Text::create('First text'));

// Insert before/after
$div->before(Comment::create('Before div'));
$div->after(Text::create('After div'));

// Replace nodes
$div->replaceWith(Element::create('section'));

// Remove nodes
$div->remove();
```

### Working with Attributes

```php
$element = Element::create('input');
// Set attributes
$element->setAttr('type', 'text');
// Get attributes
$type = $element->getAttr('type'); // 'text'
$missing = $element->getAttr('missing'); // null
// Check existence
$hasType = $element->hasAttr('type'); // true
// Remove attributes
$element->removeAttr('name');
// Get all attributes
$attrs = $element->attrs(); // ['type' => 'text']
```

### Searching and Traversal

```php
// Depth-first search
$found = $doc->dfs(fn($node) => $node instanceof Element && $node->id === 'target');

// Breadth-first search
$found = $doc->bfs(fn($node) => $node instanceof Element && $node->name === 'button');

// Find the first form
$form = $doc->querySelector('form');

// Iterate through all descendants
foreach ($doc->descendants() as $node) {
    if ($node instanceof Text) {
        echo $node->data . "\n";
    }
}
```

### HTML Serialization

```php
// Convert to string representation
$html = (string) $doc;
// or using the __toString() method
$html = $element->__toString();
```
