<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use ArrayIterator;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\NodeInterface;
use Manychois\Simdom\NodeListInterface;
use Traversable;

/**
 * Implements a live node list.
 */
class LiveNodeList implements NodeListInterface
{
    /**
     * @var array<int, NodeInterface> The list of child nodes.
     */
    private array $nodes = [];

    #region implements NodeListInterface

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->nodes);
    }

    /**
     * @inheritDoc
     */
    public function elementCount(): int
    {
        $count = 0;
        foreach ($this->nodes as $node) {
            if ($node instanceof ElementInterface) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @inheritDoc
     */
    public function find(callable $predicate): ?NodeInterface
    {
        foreach ($this->nodes as $index => $node) {
            if ($predicate($node, $index)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->nodes);
    }

    /**
     * @inheritDoc
     */
    public function indexOf(NodeInterface $node): int
    {
        $index = array_search($node, $this->nodes, true);
        if (is_int($index)) {
            return $index;
        }

        return -1;
    }

    /**
     * @inheritDoc
     */
    public function nodeAt(int $index): ?NodeInterface
    {
        if ($index < 0) {
            $index += count($this->nodes);
        }

        return $this->nodes[$index] ?? null;
    }

    #endregion

    /**
     * Appends the specified node to the end of the list.
     * The caller is responsible for ensuring correct parent-child linking.
     *
     * @param NodeInterface $node The node to append.
     */
    public function append(NodeInterface $node): void
    {
        $this->nodes[] = $node;
    }

    /**
     * Clears the list.
     * The caller is responsible for ensuring correct parent-child linking.
     */
    public function clear(): void
    {
        $this->nodes = [];
    }

    /**
     * Replace the nodes in the specified range with the new node at the same position.
     * The caller is responsible for ensuring correct parent-child linking.
     *
     * @param int                  $index    The index of the node to replace.
     * @param int                  $length   The number of nodes to remove.
     * @param array<NodeInterface> $newNodes The nodes to insert.
     */
    public function splice(int $index, int $length, array $newNodes = []): void
    {
        /**
         * @psalm-suppress MixedPropertyTypeCoercion
         */
        array_splice($this->nodes, $index, $length, $newNodes);
    }

    /**
     * Returns a concatenated string of the HTML of all nodes in the list.
     *
     * @return string The HTML of all nodes in the list.
     */
    public function toHtml(): string
    {
        $html = '';
        foreach ($this->nodes as $child) {
            $html .= $child->toHtml();
        }

        return $html;
    }
}
