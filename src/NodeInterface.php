<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a node in the DOM tree.
 */
interface NodeInterface
{
    /**
     * Returns the zero-based position of this node in its parent's child nodes.
     *
     * @return int<-1, max> The zero-based position of this node in its parent's child nodes.
     * If the node has no parent, -1 is returned.
     */
    public function index(): int;

    /**
     * Returns the element immediately following this node, if any.
     *
     * @return null|ElementInterface The element immediately following this node, if any.
     */
    public function nextElement(): ?ElementInterface;

    /**
     * Returns the node immediately following this node, if any.
     *
     * @return null|NodeInterface The node immediately following this node, if any.
     */
    public function nextNode(): ?NodeInterface;

    /**
     * Returns the node type.
     *
     * @return NodeType The node type.
     */
    public function nodeType(): NodeType;

    /**
     * Returns the parent (must be an element) of the node, or null otherwise.
     *
     * @return null|ElementInterface The parent element of the node, or null otherwise.
     */
    public function parentElement(): ?ElementInterface;

    /**
     * Returns the parent of the node, if any.
     *
     * @return null|ParentNodeInterface The parent of the node, if any.
     */
    public function parentNode(): ?ParentNodeInterface;

    /**
     * Returns the element immediately preceding this node, if any.
     *
     * @return null|ElementInterface The element immediately preceding this node, if any.
     */
    public function prevElement(): ?ElementInterface;

    /**
     * Returns the node immediately preceding this node, if any.
     *
     * @return null|NodeInterface The node immediately preceding this node, if any.
     */
    public function prevNode(): ?NodeInterface;

    /**
     * Returns the root node of the tree this node belongs to, or null if the node has no parent.
     *
     * @return null|ParentNodeInterface The root node of the tree this node belongs to, if any.
     */
    public function rootNode(): ?ParentNodeInterface;
}
