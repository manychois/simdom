<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * @template-implements IteratorAggregate<int,Element>
 */
final class HtmlCollection implements Countable, IteratorAggregate
{
    private readonly NodeList $source;
    /**
     * @var array<int,Element>
     */
    private array $elements = [];

    public function __construct(NodeList $source)
    {
        $this->source = $source;
        $this->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner();
    }

    public function at(int $index): ?Element
    {
        if ($index < 0) {
            $index += count($this->elements);
        }

        return $this->elements[$index] ?? null;
    }

    /**
     * @return Generator<int,Element>
     */
    public function filter(callable $predicate): Generator
    {
        foreach ($this->elements as $element) {
            if ($predicate($element)) {
                yield $element;
            }
        }
    }

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

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->elements);
    }

    // endregion implements IteratorAggregate

    // region internal methods

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
