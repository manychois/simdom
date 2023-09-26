<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Represents a list of nodes.
 *
 * @phpstan-extends IteratorAggregate<int, NodeInterface>
 */
interface NodeListInterface extends Countable, IteratorAggregate
{
    /**
     * Returns the number of elements in the list.
     *
     * @return int The number of elements in the list.
     */
    public function elementCount(): int;

    /**
     * Returns the first node satisfying the specified predicate.
     *
     * @param callable(NodeInterface, int): bool $predicate The function to test each node for a condition.
     *
     * @return null|NodeInterface The first node satisfying the specified predicate, or null if no node satisfies the
     * predicate.
     */
    public function find(callable $predicate): ?NodeInterface;

    /**
     * Loops through the node list.
     *
     * @return Traversable<int, NodeInterface>
     */
    public function getIterator(): Traversable;

    /**
     * Returns the index of the specified node in the list.
     *
     * @param NodeInterface $node The node to search for.
     *
     * @return int The index of the specified node in the list, or -1 if the node is not found.
     */
    public function indexOf(NodeInterface $node): int;

    /**
     * Gets the node at the specified index.
     *
     * @param int $index The index of the node to return. Negative values are counted from the end.
     *
     * @return null|NodeInterface The node at the specified index, or null if the index is out of range.
     */
    public function nodeAt(int $index): ?NodeInterface;
}
