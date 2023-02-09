<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParentNode;

/**
 * Represents an element node in a DOM tree.
 */
interface Element extends ParentNode
{
    #region Element properties

    /**
     * Returns a `NamedNodeMap` object containing the assigned attributes of the corresponding HTML element.
     */
    public function attributes(): NamedNodeMap;

    /**
     * Returns a `DOMTokenList` containing the list of class attributes.
     */
    public function classList(): DOMTokenList;

    /**
     * Returns a string representing the class of the element.
     */
    public function className(): string;

    /**
     * Sets the class of the element.
     */
    public function classNameSet(string $value): void;

    /**
     * Returns a string representing the id of the element.
     */
    public function id(): string;

    /**
     * Sets the id of the element.
     */
    public function idSet(string $value): void;

    /**
     * returns a string representing the markup of the element's content.
     */
    public function innerHTML(): string;

    /**
     * Sets the markup of the element's content.
     */
    public function innerHTMLSet(string $value): void;

    /**
     * Returns the local part of the qualified name of the element.
     */
    public function localName(): string;

    /**
     * Returns the namespace URI of the element.
     */
    public function namespaceURI(): DomNs;

    /**
     * Returns the first sibling `Element` that follows this node.
     */
    public function nextElementSibling(): ?Element;

    /**
     * Returns a string representing the markup of the element including its content.
     */
    public function outerHTML(): string;

    /**
     * Replaces this element with nodes parsed from the given string.
     */
    public function outerHTMLSet(string $value): void;

    /**
     * Returns the first sibling `Element` that precedes this node.
     */
    public function previousElementSibling(): ?Element;

    /**
     * Returns the tag name of the element. If the element is an HTML element, the tag name is returned in uppercase.
     */
    public function tagName(): string;

    #endregion

    #region Element methods (attributes related)

    /**
     * Returns the attribute value by its qualified name.
     * @param string $name The qualified name of the attribute.
     * @return null|string The attribute's value, or `null` if the attribute is not set.
     */
    public function getAttribute(string $name): ?string;

    /**
     * Returns the attribute qualified names of the element.
     * @return array<string>
     */
    public function getAttributeNames(): array;

    /**
     * Returns the attribute as an `Attr` node by its qualified name.
     * @param string $name The qualified name of the attribute.
     * @return null|Attr The attribute node, or `null` if the attribute is not set.
     */
    public function getAttributeNode(string $name): ?Attr;

    /**
     * Returns the attribute as an `Attr` node by its namespace and local name.
     * @param null|DomNs $ns The namespace of the attribute.
     * @param string $localName The local name of the attribute.
     */
    public function getAttributeNodeNS(?DomNs $ns, string $localName): ?Attr;

    /**
     * Returns the attribute value by its namespace and local name.
     * @param null|DomNs $ns The namespace of the attribute.
     * @param string $localName The local name of the attribute.
     * @return null|string The attribute's value, or `null` if the attribute is not set.
     */
    public function getAttributeNS(?DomNs $ns, string $localName): ?string;

    /**
     * Returns `true` if the element has an attribute with the given qualified name.
     * @param string $name The qualified name of the attribute.
     */
    public function hasAttribute(string $name): bool;

    /**
     * Returns `true` if the element has an attribute with the given namespace and local name.
     * @param null|DomNs $ns The namespace of the attribute.
     * @param string $localName The local name of the attribute.
     */
    public function hasAttributeNS(?DomNs $ns, string $localName): bool;

    /**
     * Returns `true` if the element has any attributes.
     */
    public function hasAttributes(): bool;

    /**
     * Removes the attribute with the given qualified name.
     * @param string $name The qualified name of the attribute.
     */
    public function removeAttribute(string $name): void;

    /**
     * Removes the given attribute node from the element.
     * @return Attr The removed attribute node.
     */
    public function removeAttributeNode(Attr $attr): Attr;

    /**
     * Removes the attribute with the given namespace and local name.
     * @param null|DomNs $ns The namespace of the attribute.
     * @param string $localName The local name of the attribute.
     */
    public function removeAttributeNS(?DomNs $ns, string $localName): void;

    /**
     * Sets the attribute with the given qualified name to the given value.
     * @param string $name The qualified name of the attribute.
     * @param string $value The value of the attribute.
     */
    public function setAttribute(string $name, string $value): void;

    /**
     * Assigns the attribute node to the element.
     * @return null|Attr The replaced attribute node, if any.
     */
    public function setAttributeNode(Attr $attr): ?Attr;

    /**
     * Sets the attribute with the given namespace and qualified name to the given value.
     * @param null|DomNs $ns The namespace of the attribute.
     * @param string $name The qualified name of the attribute.
     * @param string $value The value of the attribute.
     */
    public function setAttributeNS(?DomNs $ns, string $name, string $value): void;

    /**
     * Toggles the attribute with the given qualified name, i.e. adds it if it is not present, or removes it otherwise.
     * @param string $name The qualified name of the attribute.
     * @param null|bool $force If `true`, the attribute will be added.
     *                         If `false`, the attribute will be removed.
     *                         If `null`, the attribute will be toggled.
     * @return bool `true` if the attribute is present after the call, `false` otherwise.
     */
    public function toggleAttribute(string $name, ?bool $force = null): bool;

    #endregion

    #region Element methods (DOM manipulation)

    /**
     * Appends a set of `Node` objects or strings to the children list of its parent.
     */
    public function after(Node|string ...$nodes): void;

    /**
     * Inserts a set of `Node` objects or strings in the children list of its parent, just before it.
     */
    public function before(Node|string ...$nodes): void;

    /**
     * Removes this object from its parent children list.
     */
    public function remove(): void;

    /**
     * Replaces this object in the children list of its parent with a set of `Node` objects or strings.
     */
    public function replaceWith(Node|string ...$nodes): void;

    #endregion
}
