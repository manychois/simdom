<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Closure;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeList;
use Traversable;

class LiveNodeList implements NodeList
{
    public ?LiveNodeListObserver $observer;
    public BaseParentNode $owner;
    /**
     * @var array<int, BaseNode>
     */
    private array $nodes;

    public function __construct(BaseParentNode $owner)
    {
        $this->owner = $owner;
        $this->nodes = [];
        $this->observer = null;
    }

    #region implements NodeList properties

    public function length(): int
    {
        return count($this->nodes);
    }

    #endregion

    #region implements NodeList methods

    /**
     * @return array<Node>
     */
    public function clear(): array
    {
        foreach ($this->nodes as $node) {
            $node->parent = null;
        }
        $cleared = $this->nodes;
        $this->nodes = [];
        if ($this->observer) {
            $this->observer->onNodeListCleared($this);
        }
        return $cleared;
    }

    public function findIndex(Closure $predicate, int $start = 0): int
    {
        $len = count($this->nodes);
        if ($start < 0) {
            $start = $len + $start;
        }
        if ($start >= $len) {
            return -1;
        }
        if ($start < 0) {
            $start = 0;
        }
        for ($i = $start; $i < $len; $i++) {
            if ($predicate($this->nodes[$i], $i)) {
                return $i;
            }
        }
        return -1;
    }

    public function findLastIndex(Closure $predicate, int $start = -1): int
    {
        $len = count($this->nodes);
        if ($start < 0) {
            $start = $len + $start;
        }
        if ($start < 0) {
            return -1;
        }
        if ($start >= $len) {
            $start = $len - 1;
        }
        for ($i = $start; $i >= 0; $i--) {
            if ($predicate($this->nodes[$i], $i)) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * @return Traversable<BaseNode>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->nodes as $node) {
            yield $node;
        }
    }

    public function indexOf(Node $node): int
    {
        $found = array_search($node, $this->nodes, true);
        return $found === false ? -1 : $found;
    }

    public function item(int $index): ?BaseNode
    {
        return $this->nodes[$index] ?? null;
    }

    #endregion

    /**
     * Append nodes to the list without pre-insertion validation for performance purpose.
     * **Warnings:**
     * 1. Do not put DocumentFragment in `$nodes`, unpack its child nodes first.
     * 2. Make sure the nodes will be removed from their original parents.
     * @internal
     */
    public function simAppend(Node ...$nodes): void
    {
        foreach ($nodes as $node) {
            $node->parent = $this->owner;
            $this->nodes[] = $node;
        }
        if ($this->observer) {
            $this->observer->onNodeListAppended($this, $nodes);
        }
    }

    /**
     * Insert nodes at specific position in the list without pre-insertion validation for performance purpose.
     * **Warnings:**
     * 1. Do not put DocumentFragment in `$nodes`, unpack its child nodes first.
     * 2. Make sure the nodes will be removed from their original parents.
     * @internal
     */
    public function simInsertAt(int $index, Node ...$nodes): void
    {
        foreach ($nodes as $node) {
            $node->parent = $this->owner;
        }
        array_splice($this->nodes, $index, 0, $nodes);
        if ($this->observer) {
            $this->observer->onNodeListInserted($this, $index, $nodes);
        }
    }

    public function simRemove(Node $node): bool
    {
        $index = $this->indexOf($node);
        if ($index === -1) {
            return false;
        }
        $this->simRemoveAt($index);
        return true;
    }

    public function simRemoveAt(int $index): Node
    {
        /** @var BaseNode $node */
        $node = array_splice($this->nodes, $index, 1)[0];
        $node->parent = null;
        if ($this->observer) {
            $this->observer->onNodeListRemoved($this, $index, $node);
        }
        return $node;
    }
}
