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
     * Returns the number of child nodes of this node.
     *
     * @return int The number of child nodes.
     */
    public function childNodeCount(): int;

    /**
     * Loops through all child nodes of this node.
     *
     * @return Generator<int, NodeInterface>
     */
    public function childNodes(): Generator;

    /**
     * Returns the first child node of this node.
     *
     * @return null|NodeInterface The first child node, or null if there is no child node.
     */
    public function firstChild(): ?NodeInterface;

    /**
     * Returns the last child node of this node.
     *
     * @return null|NodeInterface The last child node, or null if there is no child node.
     */
    public function lastChild(): ?NodeInterface;

    /**
     * Removes a child node from this node.
     *
     * @param NodeInterface $node The node to remove.
     *
     * @return bool True if the node was removed, false if the node was not found.
     */
    public function removeChild(NodeInterface $node): bool;
}
