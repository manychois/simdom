# Simdom

Simdom is PHP library for parsing and manipulating DOM documents.
It has no dependency and does not require `libxml` or `dom` extension.
It is designed to be as simple as possible.
Not a full DOM implementation, it is enough for most use cases.
Regular expressions are used extensively in the parsing logic, we are sorry for that.

## Features

- No issue parsing valid HTML5 documents, e.g. It can parse boolean attributes like `readonly` and `required`.
- Type hinting is placed everywhere.
- Extra convenient methods are added to `Document`, `DocumentFragment` and `Element`.
- `NodeType` and namespace URI constants are implemented as `Enum`.
- Properties `classList` and `children` of `Element` are lazy initialized.

## Major differences from DOM standard

- XML document will still be parsed as HTML5.
- Handling of deprecated tags `frame`, `frameset`, and `plaintext` is not implemented.
  When encountered, they are treated as ordinary tags like `div`.
- `Attr` is not a `Node`.
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