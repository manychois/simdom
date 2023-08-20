<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Generator;
use Manychois\Simdom\NodeInterface;

/**
 * Represents a node which can have children.
 */
interface ParentNodeInterface extends NodeInterface
{
    /**
     * Loops through all child nodes of this node.
     *
     * @return Generator<int, NodeInterface>
     */
    public function childNodes(): Generator;

    /**
     * Removes a child node from this node.
     *
     * @param NodeInterface $node The node to remove.
     *
     * @return bool True if the node was removed, false if the node was not found.
     */
    public function removeChild(NodeInterface $node): bool;
}
