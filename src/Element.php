<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParentNode;

interface Element extends ParentNode
{
    #region Element properties

    public function attributes(): NamedNodeMap;
    public function classList(): DOMTokenList;
    public function className(): string;
    public function classNameSet(string $value): void;
    public function children(): HTMLCollection;
    public function id(): string;
    public function idSet(string $value): void;
    public function innerHTML(): string;
    public function innerHTMLSet(string $value): void;
    public function localName(): string;
    public function namespaceURI(): DomNs;
    public function nextElementSibling(): ?Element;
    public function outerHTML(): string;
    public function outerHTMLSet(string $value): void;
    public function previousElementSibling(): ?Element;
    public function tagName(): string;

    #endregion

    #region Element methods (attributes related)

    public function getAttribute(string $name): ?string;
    /**
     * @return array<string>
     */
    public function getAttributeNames(): array;
    public function getAttributeNode(string $name): ?Attr;
    public function getAttributeNodeNS(?DomNs $ns, string $localName): ?Attr;
    public function getAttributeNS(?DomNs $ns, string $localName): ?string;
    public function hasAttribute(string $name): bool;
    public function hasAttributeNS(?DomNs $ns, string $localName): bool;
    public function hasAttributes(): bool;
    public function removeAttribute(string $name): void;
    public function removeAttributeNode(Attr $attr): Attr;
    public function removeAttributeNS(?DomNs $ns, string $localName): void;
    public function setAttribute(string $name, string $value): void;
    public function setAttributeNode(Attr $attr): ?Attr;
    public function setAttributeNS(?DomNs $ns, string $name, string $value): void;
    public function toggleAttribute(string $name, ?bool $force = null): bool;

    #endregion

    #region Element methods (DOM manipulation)

    public function after(Node|string ...$nodes): void;
    public function before(Node|string ...$nodes): void;
    public function remove(): void;
    public function replaceWith(Node|string ...$nodes): void;

    #endregion
}
