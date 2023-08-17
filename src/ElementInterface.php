<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents an element node in the DOM tree.
 */
interface ElementInterface extends ParentNodeInterface
{
    /**
     * Checks if an attribute exists.
     *
     * @param string $name The name of the attribute.
     *
     * @return bool True if the attribute exists, false otherwise.
     */
    public function hasAttribute(string $name): bool;

    /**
     * Returns the local part of the qualified name of the element.
     *
     * @return string The local part of the qualified name of the element.
     */
    public function localName(): string;

    /**
     * Returns the namespace URI of the element.
     *
     * @return string The namespace URI of the element.
     */
    public function namespaceUri(): string;

    /**
     * Sets the value of an attribute.
     *
     * @param string      $name  The name of the attribute.
     * @param null|string $value The value of the attribute. `null` represents an attribute without a specified value.
     */
    public function setAttribute(string $name, ?string $value): void;
}
