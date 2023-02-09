<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents an attribute in an `Element` object.
 */
interface Attr
{
    #region Attr properties

    /**
     * Returns the local part of the qualified name of the attribute.
     */
    public function localName(): string;

    /**
     * Returns the qualified name of the attribute.
     */
    public function name(): string;

    /**
     * Returns the namespace URI of the attribute.
     */
    public function namespaceURI(): ?DomNs;

    /**
     * Returns the `Element` the attribute belongs to.
     */
    public function ownerElement(): ?Element;

    /**
     * Returns the namespace prefix of the attribute.
     */
    public function prefix(): ?string;

    /**
     * Returns the attribute's value.
     */
    public function value(): string;

    /**
     * Sets the attribute's value.
     */
    public function valueSet(string $value): void;

    #endregion
}
