<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents an attribute in an Element object.
 */
interface Attr
{
    #region Attr properties

    public function localName(): string;
    public function name(): string;
    public function namespaceURI(): ?DomNs;
    public function ownerElement(): ?Element;
    public function prefix(): ?string;
    public function value(): string;
    public function valueSet(string $value): void;

    #endregion
}
