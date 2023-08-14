<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a node in the DOM tree.
 */
interface NodeInterface
{
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
}
