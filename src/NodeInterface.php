<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\Dom\ParentNodeInterface;

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
     * Returns the node immediately following this node, if any.
     *
     * @return null|NodeInterface The node immediately following this node, if any.
     */
    public function nextSibling(): ?NodeInterface;

    /**
     * Returns the node type.
     *
     * @return NodeType The node type.
     */
    public function nodeType(): NodeType;

    /**
     * Returns the parent of the node, if any.
     *
     * @return null|ParentNodeInterface The parent of the node, if any.
     */
    public function parentNode(): ?ParentNodeInterface;

    /**
     * Returns the node immediately preceding this node, if any.
     *
     * @return null|NodeInterface The node immediately preceding this node, if any.
     */
    public function previousSibling(): ?NodeInterface;
}
