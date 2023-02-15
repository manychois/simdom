<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Closure;
use Countable;
use IteratorAggregate;
use Manychois\Simdom\Node;
use Traversable;

/**
 * Represents a collection of nodes.
 */
interface NodeList extends IteratorAggregate, Countable
{
    #region NodeList properties

    /**
     * Returns the number of nodes in the collection.
     */
    public function length(): int;

    #endregion

    #region NodeList methods

    /**
     * @return Traversable<Node>
     */
    public function getIterator(): Traversable;

    /**
     * Returns the node at the specified index, or null if the index is out of range.
     */
    public function item(int $index): ?Node;

    #endregion

    #region non-standard methods

    /**
     * Removes all nodes from the collection.
     * @return array<Node> The removed nodes.
     */
    public function clear(): array;

    /**
     * Returns the index of the first node in the collection that satisfies the given predicate.
     * @param Closure $predicate Function that takes the node and its index, and returns a boolean.
     * @param int $start The index to start searching from.
     *                   If negative, the search starts from the end of the collection.
     * @return int The index of the first node that satisfies the predicate, or -1 if not found.
     */
    public function findIndex(Closure $predicate, int $start = 0): int;

    /**
     * Returns the index of the first node in the collection that satisfies the given predicate, but in reverse order.
     * @param Closure $predicate Function that takes the node and its index, and returns a boolean.
     * @param int $start The index to start searching from.
     *                   If negative, the search starts from the end of the collection.
     * @return int The index of the first node that satisfies the predicate, or -1 if not found.
     */
    public function findLastIndex(Closure $predicate, int $start = -1): int;

    /**
     * Returns the index of the given node in the collection.
     * @param Node $node The node to search for.
     * @return int The index of the node, or -1 if not found.
     */
    public function indexOf(Node $node): int;

    #endregion
}
