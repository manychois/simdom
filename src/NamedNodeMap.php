<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use IteratorAggregate;
use Manychois\Simdom\Attr;
use Manychois\Simdom\DomNs;
use Traversable;

interface NamedNodeMap extends IteratorAggregate
{
    #region NamedNodeMap properties

    public function length(): int;

    #endregion

    #region NamedNodeMap methods

    /**
     * @return Traversable<Attr>
     */
    public function getIterator(): Traversable;
    public function getNamedItem(string $name): ?Attr;
    public function getNamedItemNS(?DomNs $ns, string $localName): ?Attr;
    public function item(int $index): ?Attr;
    public function removeNamedItem(string $name): Attr;
    public function removeNamedItemNS(?DomNs $ns, string $localName): Attr;
    public function setNamedItem(Attr $attr): ?Attr;

    #endregion
}
