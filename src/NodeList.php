<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Closure;
use IteratorAggregate;
use Manychois\Simdom\Node;
use Traversable;

interface NodeList extends IteratorAggregate
{
    #region NodeList properties

    public function length(): int;

    #endregion

    #region NodeList methods

    /**
     * @return Traversable<Node>
     */
    public function getIterator(): Traversable;
    public function item(int $index): ?Node;

    #endregion

    #region non-standard methods

    /**
     * @return array<Node>
     */
    public function clear(): array;
    public function findIndex(Closure $predicate, int $start = 0): int;
    public function findLastIndex(Closure $predicate, int $start = -1): int;
    public function indexOf(Node $node): int;

    #endregion
}
