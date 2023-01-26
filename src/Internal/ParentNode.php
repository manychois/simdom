<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Element;
use Manychois\Simdom\HTMLCollection;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeList;
use Traversable;

interface ParentNode extends Node
{
    #region properties shared by Document, DocumentFragment and Element

    public function childElementCount(): int;
    public function childNodes(): NodeList;
    public function children(): HTMLCollection;
    public function firstChild(): ?Node;
    public function firstElementChild(): ?Element;
    public function lastChild(): ?Node;
    public function lastElementChild(): ?Element;

    #endregion

    #region methods shared by Document, DocumentFragment and Element

    public function append(Node|string ...$nodes): void;
    public function appendChild(Node $node): Node;
    public function contains(Node $node): bool;
    public function hasChildNodes(): bool;
    public function insertBefore(Node $node, ?Node $ref): Node;
    public function normalize(): void;
    public function prepend(Node|string ...$nodes): void;
    public function removeChild(Node $node): Node;
    public function replaceChild(Node $new, Node $old): Node;
    public function replaceChildren(Node|string ...$nodes): void;

    #endregion

    #region non-standard methods

    /**
     * @return Traversable<Node>
     */
    public function dfs(): Traversable;
    /**
     * @return Traversable<Element>
     */
    public function dfsElements(): Traversable;

    #endregion
}
