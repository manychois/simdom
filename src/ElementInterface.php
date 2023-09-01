<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Generator;

/**
 * Represents an element node in the DOM tree.
 */
interface ElementInterface extends ParentNodeInterface
{
    /**
     * Loops through the attributes of the element.
     *
     * @return Generator<string, null|string> A generator that yields the name and value of each attribute.
     */
    public function attributes(): Generator;

    /**
     * Returns the value of an attribute.
     *
     * @param string $name The name of the attribute.
     *
     * @return null|string The value of the attribute.
     * `null` represents an attribute without a specified value, or if the attribute does not exist.
     */
    public function getAttribute(string $name): ?string;

    /**
     * Checks if an attribute exists.
     *
     * @param string $name The name of the attribute.
     *
     * @return bool True if the attribute exists, false otherwise.
     */
    public function hasAttribute(string $name): bool;

    /**
     * Returns the HTML markup contained within the element.
     *
     * @return string The HTML markup contained within the element.
     */
    public function innerHtml(): string;

    /**
     * Returns the local part of the qualified name of the element.
     *
     * @return string The local part of the qualified name of the element.
     */
    public function localName(): string;

    /**
     * Returns the namespace URI of the element.
     *
     * @return NamespaceUri The namespace URI of the element.
     */
    public function namespaceUri(): NamespaceUri;

    /**
     * Sets the value of an attribute.
     *
     * @param string      $name  The name of the attribute.
     * @param null|string $value The value of the attribute. `null` represents an attribute without a specified value.
     *
     * @return ElementInterface The element itself. This allows chaining of method calls.
     */
    public function setAttribute(string $name, ?string $value): ElementInterface;

    /**
     * Sets the HTML markup contained within the element.
     *
     * @param string $html The HTML markup contained within the element.
     *
     * @return ElementInterface The element itself. This allows chaining of method calls.
     */
    public function setInnerHtml(string $html): ElementInterface;

    /**
     * Returns the tag name of the element.
     * If the element is an HTML element, the tag name is in uppercase.
     */
    public function tagName(): string;
}
