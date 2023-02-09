<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use IteratorAggregate;
use Traversable;

/**
 * Represents a collection of elements.
 */
interface HTMLCollection extends IteratorAggregate
{
    #region HTMLCollection properties

    /**
     * Returns the number of elements in the collection.
     */
    public function length(): int;

    #endregion

    #region HTMLCollection methods

    /**
     * @return Traversable<Element>
     */
    public function getIterator(): Traversable;

    /**
     * Returns the element at the specified index, or null if the index is out of range.
     */
    public function item(int $index): ?Element;

    #endregion
}
