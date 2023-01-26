<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use IteratorAggregate;
use Traversable;

interface HTMLCollection extends IteratorAggregate
{
    #region HTMLCollection properties

    public function length(): int;

    #endregion

    #region HTMLCollection methods

    /**
     * @return Traversable<Element>
     */
    public function getIterator(): Traversable;
    public function item(int $index): ?Element;

    #endregion
}
