<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\AbstractParentNode;

/**
 * Represents a list of direct child nodes of an element.
 *
 * @template-implements \IteratorAggregate<int,AbstractNode>
 */
class ChildNodeList implements \Countable, \IteratorAggregate
{
    /**
     * @var array<int,AbstractNode>
     */
    private array $list = [];

    /**
     * Filters this list and returns the elements.
     *
     * @return \Generator<int,Element> The elements in this list.
     */
    public function elements(): \Generator
    {
        foreach ($this->list as $node) {
            if (!($node instanceof Element)) {
                continue;
            }

            yield $node;
        }
    }

    /**
     * Gets the node at the specified index.
     * Negative index is counted from the end of the list.
     *
     * @param int $index The zero-based index of the node to get.
     *
     * @return AbstractNode|null The node at the specified index, or null if the index is out of range.
     */
    public function get(int $index): ?AbstractNode
    {
        $count = \count($this->list);
        if ($index < 0) {
            $index += $count;
        }

        return $this->list[$index] ?? null;
    }

    /**
     * Iterates over the child nodes in this list in reverse order.
     *
     * @return \Generator<int,AbstractNode> The child nodes in this list in reverse order.
     */
    public function reverse(): \Generator
    {
        for ($i = \count($this->list) - 1; $i >= 0; --$i) {
            yield $this->list[$i];
        }
    }

    /**
     * Returns an array of the child nodes in this list.
     *
     * @return array<int,AbstractNode> An array of the child nodes in this list.
     */
    public function toArray(): array
    {
        return $this->list;
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    #region Internal methods

    /**
     * Appends detached nodes to the end of this list.
     * The index and the owner of the appended nodes will be updated.
     *
     * @param AbstractParentNode  $owner    The owner node of the appended node.
     * @param string|AbstractNode ...$nodes The node to append.
     *
     * @internal
     */
    public function 🚫append(AbstractParentNode $owner, string|AbstractNode ...$nodes): void
    {
        $n = \count($this->list);
        foreach ($nodes as $node) {
            \assert(\is_string($node) || $node->parent() === null);
            if (\is_string($node)) {
                $node = new Text($node);
            }
            $node->🚫setIndex($n);
            $node->🚫setOwner($owner);
            $this->list[] = $node;
            ++$n;
        }
    }

    /**
     * Clears this list.
     * The index and the owner of the removed node are updated.
     *
     * @internal
     */
    public function 🚫clear(): void
    {
        foreach ($this->list as $node) {
            $node->🚫setIndex(-1);
            $node->🚫setOwner(null);
        }
        $this->list = [];
    }

    /**
     * Inserts detached nodes at the specified index of this list.
     * The index and the owner of the inserted node are updated.
     *
     * @param AbstractParentNode  $owner    The owner node of the inserted node.
     * @param int                 $at       The index to insert the nodes.
     * @param string|AbstractNode ...$nodes The nodes to insert.
     */
    public function 🚫insertAt(AbstractParentNode $owner, int $at, string|AbstractNode ...$nodes): void
    {
        $converted = [];
        foreach ($nodes as $node) {
            \assert(\is_string($node) || $node->parent() === null);
            if (\is_string($node)) {
                $node = new Text($node);
            }
            $node->🚫setOwner($owner);
            $converted[] = $node;
        }
        \array_splice($this->list, $at, 0, $converted);
        $n = \count($this->list);
        for ($i = $at; $i < $n; ++$i) {
            $this->list[$i]->🚫setIndex($i + 1);
        }
    }

    /**
     * Removes nodes from this list.
     * The index and the owner of the removed node are updated.
     *
     * @param AbstractNode ...$nodes The nodes to remove.
     *
     * @internal
     */
    public function 🚫remove(AbstractNode ...$nodes): void
    {
        if (\count($nodes) === 1) {
            $node = $nodes[0];
            \array_splice($this->list, $node->index(), 1);
            $node->🚫setIndex(-1);
            $node->🚫setOwner(null);
        } else {
            $maps = [];
            foreach ($nodes as $node) {
                $maps[$node->index()] = $node;
            }
            \uksort($maps, static fn ($a, $b) => $b <=> $a);
            foreach ($maps as $i => $node) {
                \assert($this->list[$i] === $node);
                \array_splice($this->list, $i, 1);
                $node->🚫setIndex(-1);
                $node->🚫setOwner(null);
            }
            $n = \count($this->list);
            $fromI = \array_key_last($maps);
            if (!\is_int($fromI)) {
                return;
            }
            for ($i = $fromI; $i < $n; ++$i) {
                $node = $this->list[$i];
                $node->🚫setIndex($i);
            }
        }
    }

    #endregion Internal methods
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    #region implements \Countable

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return \count($this->list);
    }

    #endregion implements \Countable

    #region implements \IteratorAggregate

    /**
     * @inheritDoc
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->list);
    }

    #endregion implements \IteratorAggregate
}
