<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents the Dcument Type Declaration (DTD).
 */
interface DocumentType extends Node
{
    #region DocumentType properties

    /**
     * Returns the name of the document type.
     */
    public function name(): string;

    /**
     * Returns the public identifier of the document type.
     */
    public function publicId(): string;

    /**
     * Returns the system identifier of the document type.
     */
    public function systemId(): string;

    #endregion

    #region DocumentType methods

    /**
     * Appends a set of `Node` objects or strings to the children list of its parent.
     * @param Node|string ...$nodes The `Node` objects or strings to append.
     */
    public function after(...$nodes): void;

    /**
     * Inserts a set of `Node` objects or strings in the children list of its parent, just before it.
     * @param Node|string ...$nodes The `Node` objects or strings to insert.
     */
    public function before(...$nodes): void;

    /**
     * Removes this object from its parent children list.
     */
    public function remove(): void;

    /**
     * Replaces this object in the children list of its parent with a set of `Node` objects or strings.
     * @param Node|string ...$nodes The `Node` objects or strings to replace this object with.
     */
    public function replaceWith(...$nodes): void;

    #endregion
}
