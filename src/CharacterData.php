<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a `Node` object that contains characters. `Comment` and `Text` inherit from it.
 */
interface CharacterData extends Node
{
    #region CharacterData properties

    /**
     * Returns the textual data contained in this object.
     */
    public function data(): string;

    /**
     * Sets the textual data contained in this object.
     */
    public function dataSet(string $data): void;

    /**
     * Returns the first sibling `Element` that follows this node.
     */
    public function nextElementSibling(): ?Element;

    /**
     * Returns the first sibling `Element` that precedes this node.
     */
    public function previousElementSibling(): ?Element;

    #endregion

    #region CharacterData methods

    /**
     * Appends a set of `Node` objects or strings to the children list of its parent.
     * @param Node|string ...$nodes The `Node` objects or strings to append.
     */
    public function after(...$nodes): void;

    /**
     * Appends the given string to its textual data.
     */
    public function appendData(string $data): void;

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
