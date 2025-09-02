<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * Holds a collection of HTML elements.
 *
 * @template-implements IteratorAggregate<int,Element>
 */
final class HtmlCollection implements Countable, IteratorAggregate
{
    private readonly NodeList $source;
    /**
     * @var array<int,Element>
     */
    private array $elements = [];

    /**
     * Creates a new HtmlCollection.
     *
     * @param NodeList $source the source NodeList
     */
    public function __construct(NodeList $source)
    {
        $this->source = $source;
        $this->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner();
    }

    /**
     * Returns the element at the specified index.
     *
     * @param int $index The index of the element to retrieve.
     *                   If negative, counts from the end of the collection.
     *
     * @return Element|null the element at the specified index, or null if not found
     */
    public function at(int $index): ?Element
    {
        if ($index < 0) {
            $index += count($this->elements);
        }

        return $this->elements[$index] ?? null;
    }

    /**
     * Filters the elements in the collection based on a predicate.
     *
     * @param callable $predicate the predicate function to filter elements
     *
     * @return Generator<int,Element> the filtered elements
     */
    public function filter(callable $predicate): Generator
    {
        foreach ($this->elements as $element) {
            if ($predicate($element)) {
                yield $element;
            }
        }
    }

    /**
     * Returns the index of the specified element in the collection.
     *
     * @param Element $element the element to find
     *
     * @return int the index of the element, or -1 if not found
     */
    public function indexOf(Element $element): int
    {
        $index = array_search($element, $this->elements, true);
        if (false === $index) {
            return -1;
        }

        return $index;
    }

    // region implements Countable

    public function count(): int
    {
        return count($this->elements);
    }

    // endregion implements Countable

    // region implements IteratorAggregate

    /**
     * @return Traversable<int,Element> the iterator for the collection
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }

    // endregion implements IteratorAggregate

    // region internal methods

    /**
     * Synchronizes the collection with its source NodeList.
     */
    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner(): void
    {
        /**
         * @var array<int,Element> $elements
         */
        $elements = iterator_to_array(
            $this->source->filter(static fn (AbstractNode $node): bool => $node instanceof Element)
        );
        $this->elements = $elements;
    }

    // endregion internal methods
}
