<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use Traversable;
use WeakReference;

/**
 * @implements IteratorAggregate<int,Comment|Doctype|Element|Text>
 */
final class NodeList implements Countable, IteratorAggregate
{
    public readonly AbstractParentNode $owner;
    /**
     * @var array<int,Comment|Doctype|Element|Text>
     */
    private array $nodes = [];
    /**
     * @var null|WeakReference<HtmlCollection>
     */
    private ?WeakReference $elementListRef = null;

    public function __construct(AbstractParentNode $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return array<int,Comment|Doctype|Element|Text>
     */
    public function asArray(): array
    {
        return $this->nodes;
    }

    public function at(int $index): Comment|Doctype|Element|Text|null
    {
        if ($index < 0) {
            $index = count($this->nodes) + $index;
        }

        return $this->nodes[$index] ?? null;
    }

    public function equals(NodeList $other): bool
    {
        if (count($this->nodes) !== count($other->nodes)) {
            return false;
        }

        foreach ($this->nodes as $index => $node) {
            if (!$node->equals($other->nodes[$index])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Generator<int,Comment|Doctype|Element|Text>
     */
    public function filter(callable $predicate): Generator
    {
        foreach ($this->nodes as $node) {
            if ($predicate($node)) {
                yield $node;
            }
        }
    }

    /**
     * Finds the first node that matches the predicate.
     *
     * @param callable $predicate a function that takes a node and returns true if it matches
     *
     * @return Comment|Doctype|Element|Text|null the first matching node, or null if none found
     */
    public function find(callable $predicate, int $since = 0): Comment|Doctype|Element|Text|null
    {
        $count = count($this->nodes);
        $since = $since < 0 ? $count + $since : $since;
        for ($i = $since; $i < $count; ++$i) {
            if ($predicate($this->nodes[$i])) {
                return $this->nodes[$i];
            }
        }

        return null;
    }

    public function findElement(callable $predicate, int $since = 0): ?Element
    {
        $count = count($this->nodes);
        $since = $since < 0 ? $count + $since : $since;
        for ($i = $since; $i < $count; ++$i) {
            if ($this->nodes[$i] instanceof Element && $predicate($this->nodes[$i])) {
                return $this->nodes[$i];
            }
        }

        return null;
    }

    public function findLast(callable $predicate, int $since = -1): Comment|Doctype|Element|Text|null
    {
        $count = count($this->nodes);
        $since = $since < 0 ? $count + $since : $since;
        for ($i = $since; $i >= 0; --$i) {
            if ($predicate($this->nodes[$i])) {
                return $this->nodes[$i];
            }
        }

        return null;
    }

    public function findLastElement(callable $predicate, int $since = -1): ?Element
    {
        $count = count($this->nodes);
        $since = $since < 0 ? $count + $since : $since;
        for ($i = $since; $i >= 0; --$i) {
            if ($this->nodes[$i] instanceof Element && $predicate($this->nodes[$i])) {
                return $this->nodes[$i];
            }
        }

        return null;
    }

    // region implements Countable

    public function count(): int
    {
        return count($this->nodes);
    }

    // endregion implements Countable

    // region implements IteratorAggregate

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->nodes);
    }

    // endregion implements IteratorAggregate

    // region internal methods

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append(AbstractNode ...$nodes): void
    {
        assert(
            array_reduce(
                $nodes,
                static fn (bool $carry, AbstractNode $node) => $carry && null === $node->parent,
                true
            ),
            'All nodes must not have a parent'
        );
        assert(
            array_reduce(
                $nodes,
                static fn (bool $carry, AbstractNode $node) => $carry && self::isValidItem($node),
                true
            ),
            'All nodes must be instances of Comment, Doctype, Element, or Text'
        );

        $i = count($this->nodes);
        array_push($this->nodes, ...$nodes);
        foreach ($nodes as $node) {
            $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetParent($this->owner);
            $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex($i++);
        }
        $this->syncElementList();
    }

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Clear(): void
    {
        foreach ($this->nodes as $node) {
            $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetParent(null);
            $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex(-1);
        }
        $this->nodes = [];
        $this->syncElementList();
    }

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙GetElementList(): HtmlCollection
    {
        $elementList = $this->elementListRef?->get();
        if ($elementList instanceof HtmlCollection) {
            return $elementList;
        }

        $elementList = new HtmlCollection($this);
        $this->elementListRef = WeakReference::create($elementList);

        return $elementList;
    }

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt(int $index, AbstractNode ...$nodes): void
    {
        assert(
            array_reduce(
                $nodes,
                static fn (bool $carry, AbstractNode $node) => $carry && null === $node->parent,
                true
            ),
            'All nodes must not have a parent'
        );
        assert(
            array_reduce(
                $nodes,
                static fn (bool $carry, AbstractNode $node) => $carry && self::isValidItem($node),
                true
            ),
            'All nodes must be instances of Comment, Doctype, Element, or Text'
        );
        array_splice($this->nodes, $index, 0, $nodes);
        foreach ($nodes as $node) {
            $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetParent($this->owner);
        }
        // Update indices of subsequent nodes
        for ($j = $index; $j < count($this->nodes); ++$j) {
            $this->nodes[$j]->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex($j);
        }
        $this->syncElementList();
    }

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙normalise(): void
    {
        $normalised = [];
        $i = -1;
        $toRemove = [];
        foreach ($this->nodes as $node) {
            if ($node instanceof Text) {
                if ('' === $node->data) {
                    $toRemove[] = $node;
                } else {
                    if ($i >= 0 && $normalised[$i] instanceof Text) {
                        $normalised[$i]->data .= $node->data;
                        $toRemove[] = $node;
                    } else {
                        $normalised[] = $node;
                        ++$i;
                    }
                }
            } else {
                $normalised[] = $node;
                ++$i;
            }
        }

        if (count($toRemove) > 0) {
            $resetIndexFrom = $toRemove[0]->index;
            foreach ($toRemove as $node) {
                $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetParent(null);
                $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex(-1);
            }
            $this->nodes = $normalised;
            // Update indices of remaining nodes
            for ($j = $resetIndexFrom; $j < count($this->nodes); ++$j) {
                $this->nodes[$j]->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex($j);
            }
            $this->syncElementList();
        }
    }

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙remove(AbstractNode $node): void
    {
        assert($node->parent === $this->owner, 'Node does not belong to this parent');
        assert($this->nodes[$node->index] === $node, 'Node index does not match');

        $i = $node->index;
        array_splice($this->nodes, $i, 1);
        $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetParent(null);
        $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex(-1);

        // Update indices of subsequent nodes
        for ($j = $i; $j < count($this->nodes); ++$j) {
            $this->nodes[$j]->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex($j);
        }
        $this->syncElementList();
    }

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙replaceAt(int $index, AbstractNode ...$nodes): void
    {
        assert(
            array_reduce(
                $nodes,
                static fn (bool $carry, AbstractNode $node) => $carry && null === $node->parent,
                true
            ),
            'All nodes must not have a parent'
        );
        assert(
            array_reduce(
                $nodes,
                static fn (bool $carry, AbstractNode $node) => $carry && self::isValidItem($node),
                true
            ),
            'All nodes must be instances of Comment, Doctype, Element, or Text'
        );

        // Insert new nodes at index
        $removed = array_splice($this->nodes, $index, 1, $nodes);

        // Set parent and index for replaced node
        $removed[0]->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙setParent(null);
        $removed[0]->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙setIndex(-1);

        foreach ($nodes as $node) {
            $node->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetParent($this->owner);
        }
        // Update indices of subsequent nodes
        for ($j = $index; $j < count($this->nodes); ++$j) {
            $this->nodes[$j]->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex($j);
        }
        $this->syncElementList();
    }

    // endregion internal methods

    private static function isValidItem(AbstractNode $node): bool
    {
        return $node instanceof Comment || $node instanceof Doctype || $node instanceof Element || $node instanceof Text;
    }

    private function syncElementList(): void
    {
        if (null === $this->elementListRef) {
            return;
        }
        $elementList = $this->elementListRef->get();
        if ($elementList instanceof HtmlCollection) {
            $elementList->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner();
        }
    }
}
