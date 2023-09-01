<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Generator;

/**
 * Represents a node which can have children.
 * It can be an element, a document, or a document fragment.
 */
interface ParentNodeInterface extends NodeInterface
{
    /**
     * Appends the specified nodes to the end of this node's child nodes.
     *
     * @param string|NodeInterface ...$nodes The nodes to append.
     *                                       Strings are inserted as equivalent Text nodes.
     */
    public function append(string|NodeInterface ...$nodes): void;

    /**
     * Returns the number of child elements of this node.
     *
     * @return int<0, max> The number of child elements.
     */
    public function childElementCount(): int;

    /**
     * Returns the child node at the specified index, or null if the index is out of range.
     *
     * @param int $index The index of the child node to return.
     *
     * @return null|NodeInterface The child node at the specified index, or null if the index is out of range.
     */
    public function childNodeAt(int $index): ?NodeInterface;

    /**
     * Returns the number of child nodes of this node.
     *
     * @return int<0, max> The number of child nodes.
     */
    public function childNodeCount(): int;

    /**
     * Loops through all child nodes of this node.
     *
     * @return Generator<int, NodeInterface>
     */
    public function childNodes(): Generator;

    /**
     * Checks if this node contains the specified node.
     * A node is considered to contain itself.
     *
     * @param NodeInterface $node The node to check.
     *
     * @return bool True if this node contains the specified node, false otherwise.
     */
    public function contains(NodeInterface $node): bool;

    /**
     * Returns the first child node that matches the specified predicate, or null if not found.
     *
     * @param callable $predicate The predicate to match. It should return the child node based on the given child node
     *                            and its index.
     *
     * @return null|NodeInterface The first child node that matches the specified predicate, or null if not found.
     */
    public function find(callable $predicate): ?NodeInterface;

    /**
     * Returns the index of the first child node that matches the specified predicate.
     *
     * @param callable $predicate The predicate to match. It should return boolean value based on the given child node
     *                            and its index.
     *
     * @return int<-1, max> The index of the first child node that matches the specified predicate, or -1 if not found.
     */
    public function findIndex(callable $predicate): int;

    /**
     * Returns the first child node of this node, if any.
     *
     * @return null|NodeInterface The first child node, or null if there is no child node.
     */
    public function firstChild(): ?NodeInterface;

    /**
     * Returns the first child element of this node, if any.
     *
     * @return null|ElementInterface The first child element, or null if there is no child element.
     */
    public function firstElementChild(): ?ElementInterface;

    /**
     * Inserts the specified nodes after the specified reference node in the child nodes of this node.
     *
     * @param null|NodeInterface   $ref      The reference node which the nodes will be inserted after.
     *                                       If null, the nodes will be appended to the end of the
     *                                       child nodes.
     * @param string|NodeInterface ...$nodes The nodes to insert. Strings are inserted as equivalent Text nodes.
     */
    public function insertBefore(?NodeInterface $ref, string|NodeInterface ...$nodes): void;

    /**
     * Returns the last child node of this node, if any.
     *
     * @return null|NodeInterface The last child node, or null if there is no child node.
     */
    public function lastChild(): ?NodeInterface;

    /**
     * Returns the last child element of this node, if any.
     *
     * @return null|ElementInterface The last child element, or null if there is no child element.
     */
    public function lastElementChild(): ?ElementInterface;

    /**
     * Prepends the specified nodes to the beginning of this node's child nodes.
     *
     * @param string|NodeInterface ...$nodes The nodes to prepend.
     *                                       Strings are inserted as equivalent Text nodes.
     */
    public function prepend(string|NodeInterface ...$nodes): void;

    /**
     * Removes a child node from this node.
     *
     * @param NodeInterface $node The node to remove.
     *
     * @return bool True if the node was removed, false if the node was not found.
     */
    public function removeChild(NodeInterface $node): bool;

    /**
     * Replaces a child node with the specified nodes.
     *
     * @param NodeInterface        $old         The child node to replace.
     * @param string|NodeInterface ...$newNodes The nodes to replace the old node with.
     *                                          Strings are inserted as equivalent Text nodes.
     */
    public function replace(NodeInterface $old, string|NodeInterface ...$newNodes): void;
}
