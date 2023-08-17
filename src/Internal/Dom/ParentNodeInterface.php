<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\NodeInterface;

/**
 * Represents a node which can have children.
 */
interface ParentNodeInterface extends NodeInterface
{
    /**
     * Removes a child node from this node.
     *
     * @param NodeInterface $node The node to remove.
     *
     * @return bool True if the node was removed, false if the node was not found.
     */
    public function removeChild(NodeInterface $node): bool;
}
