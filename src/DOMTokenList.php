<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Represents a list of space-separated tokens.
 */
interface DOMTokenList extends IteratorAggregate, Countable
{
    #region DOMTokenList properties

    /**
     * Returns the number of tokens in the list.
     */
    public function length(): int;

    /**
     * Returns the string value of the list.
     */
    public function value(): string;

    #endregion

    #region DOMTokenList methods

    /**
     * Adds the specified tokens to the list.
     */
    public function add(string ...$tokens): void;

    /**
     * Returns true if the list contains the given token, otherwise false.
     */
    public function contains(string $token): bool;

    /**
     * Returns the token at the specified index, or null if the index is out of range.
     */
    public function item(int $index): ?string;

    /**
     * Removes the specified tokens from the list.
     */
    public function remove(string ...$tokens): void;

    /**
     * Replaces the token with another one.
     * @return bool Returns true if the token was replaced, otherwise false.
     */
    public function replace(string $old, string $new): bool;

    /**
     * Removes an existing token from the list, or adds it if it is not in the lst.
     * @param string $token The token to be toggled.
     * @param bool|null $force If true, the token is added, if false, the token is removed.
     * @return bool Returns true if the token is in the list after the call, otherwise false.
     */
    public function toggle(string $token, ?bool $force = null): bool;

    #endregion

    /**
     * @return Traversable<string>
     */
    public function getIterator(): Traversable;
}
