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
     * Returns a list of child nodes of this node.
     *
     * @return NodeListInterface A list of child nodes.
     */
    public function childNodes(): NodeListInterface;

    /**
     * Removes all child nodes from this node.
     */
    public function clear(): void;

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
     * Loops through all descendant nodes of this node in document order.
     *
     * @return Generator<int, NodeInterface> A generator that yields each descendant node of this node.
     */
    public function descendantNodes(): Generator;

    /**
     * Loops through all descendant elements of this node in document order.
     *
     * @return Generator<int, ElementInterface> A generator that yields each descendant element of this node.
     */
    public function descendantElements(): Generator;

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
     * Returns the first descendant element that matches the specified selector, or null if not found.
     *
     * @param string $selector The selector to match.
     *
     * @return null|ElementInterface The first descendant element that matches the specified selector, or null if not
     * found.
     */
    public function querySelector(string $selector): ?ElementInterface;

    /**
     * Returns all descendant elements that match the specified selector.
     *
     * @param string $selector The selector to match.
     *
     * @return Generator<int, ElementInterface> A generator that yields each descendant element that matches the
     * specified selector.
     */
    public function querySelectorAll(string $selector): Generator;

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
