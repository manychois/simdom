<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use IteratorAggregate;
use Manychois\Simdom\Attr;
use Traversable;

/**
 * Represents a collection of attributes.
 */
interface NamedNodeMap extends IteratorAggregate
{
    #region NamedNodeMap properties

    /**
     * Returns the number of attributes in the collection.
     */
    public function length(): int;

    #endregion

    #region NamedNodeMap methods

    /**
     * @return Traversable<Attr>
     */
    public function getIterator(): Traversable;

    /**
     * Returns the `Attr` corresponding to the given name, or null if not found.
     * @param string $name The quantified name of the attribute.
     */
    public function getNamedItem(string $name): ?Attr;

    /**
     * Returns the `Attr` corresponding to the given namespace and local name, or null if not found.
     * @param string|null $ns The namespace of the attribute.
     * @param string $localName The local name of the attribute.
     */
    public function getNamedItemNS(?string $ns, string $localName): ?Attr;

    /**
     * Returns the attribute at the given index, or null if the index is out of range.
     */
    public function item(int $index): ?Attr;

    /**
     * Removes the attribute corresponding to the given name.
     * @param string $name The quantified name of the attribute.
     * @return Attr The removed attribute.
     */
    public function removeNamedItem(string $name): Attr;

    /**
     * Removes the attribute corresponding to the given namespace and local name.
     * @param null|string $ns The namespace of the attribute.
     * @param string $localName The local name of the attribute.
     * @return Attr The removed attribute.
     */
    public function removeNamedItemNS(?string $ns, string $localName): Attr;

    /**
     * Inserts the attribute by its name in the collection.
     * @param Attr $attr The attribute to insert.
     * @return Attr|null The replaced attribute, or null if no attribute was replaced.
     */
    public function setNamedItem(Attr $attr): ?Attr;

    #endregion
}
