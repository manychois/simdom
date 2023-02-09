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

    /**
     * Returns the number of child nodes which are also `Element`.
     */
    public function childElementCount(): int;

    /**
     * Returns a `NodeList` which contains all child `Node`s.
     */
    public function childNodes(): NodeList;

    /**
     * Returns a live `HTMLCollection` which contains all child nodes which are also `Element`.
     */
    public function children(): HTMLCollection;

    /**
     * Returns the first child `Node` or `null` if there is no child.
     */
    public function firstChild(): ?Node;

    /**
     * Returns the first child `Element` or `null` if there is no child.
     */
    public function firstElementChild(): ?Element;

    /**
     * Returns the last child `Node` or `null` if there is no child.
     */
    public function lastChild(): ?Node;

    /**
     * Returns the last child `Element` or `null` if there is no child.
     */
    public function lastElementChild(): ?Element;

    #endregion

    #region methods shared by Document, DocumentFragment and Element

    /**
     * Inserts a set of `Node` or `string` after the last child of this node.
     * @param Node|string<(Node|string)> $nodes
     */
    public function append(Node|string ...$nodes): void;

    /**
     * Adds the specified childNode as the last child to this node.
     * @return Node The added child node, i.e. `$node`.
     */
    public function appendChild(Node $node): Node;

    /**
     * Indicates whether or not a node is this node or a descendant of this node.
     */
    public function contains(Node $node): bool;

    /**
     * Indicates if this node has any child nodes.
     */
    public function hasChildNodes(): bool;

    /**
     * Inserts a `Node` before the reference node as a child of this node.
     * @param Node $node The node to insert.
     * @param null|Node $ref The reference node, i.e. the node before which `$node` will be inserted.
     *                       If `$ref` is `null`, `$node` will be inserted as the last child.
     * @return Node The inserted node, i.e. `$node`.
     */
    public function insertBefore(Node $node, ?Node $ref): Node;
    public function normalize(): void;

    /**
     * Inserts a set of `Node` or string before the first child of this node.
     * @param Node|string<(Node|string)> $nodes
     */
    public function prepend(Node|string ...$nodes): void;

    /**
     * Removes a child node from this node.
     * @param Node $node The node to remove.
     * @return Node The removed node, i.e. `$node`.
     */
    public function removeChild(Node $node): Node;

    /**
     * Replaces a child node with a new node.
     * @param Node $new The new node.
     * @param Node $old The old node to be replaced.
     * @return Node The replaced node, i.e. `$old`.
     */
    public function replaceChild(Node $new, Node $old): Node;

    /**
     * Replaces all of its child nodes with a specified new set of children.
     */
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
