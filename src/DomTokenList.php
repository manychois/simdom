<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Stringable;
use Traversable;

/**
 * Represents a set of space-separated tokens.
 *
 * @template-implements IteratorAggregate<int,string>
 */
final class DomTokenList implements Countable, IteratorAggregate, Stringable
{
    private readonly Element $owner;
    private readonly string $attrName;
    /**
     * @var array<int,string>
     */
    private array $tokens = [];

    /**
     * Constructs a new DomTokenList.
     *
     * @param Element $owner    the element that owns this token list
     * @param string  $attrName the attribute name associated with this token list
     */
    public function __construct(Element $owner, string $attrName)
    {
        $this->owner = $owner;
        $this->attrName = $attrName;
        $this->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner();
    }

    /**
     * Adds one or more tokens to the list.
     *
     * @param string ...$tokens The tokens to add.
     */
    public function add(string ...$tokens): void
    {
        $added = false;
        foreach ($tokens as $token) {
            if (!$this->contains($token)) {
                $this->tokens[] = $token;
                $added = true;
            }
        }

        if ($added) {
            $this->syncToOwner();
        }
    }

    /**
     * Checks if the list contains a specific token.
     *
     * @param string $token the token to check
     *
     * @return bool true if the token is in the list, false otherwise
     */
    public function contains(string $token): bool
    {
        if ('' === $token || 1 === preg_match('/\s/', $token)) {
            throw new InvalidArgumentException(sprintf('Invalid token: "%s"', $token));
        }

        return in_array($token, $this->tokens, true);
    }

    /**
     * Removes one or more tokens from the list.
     *
     * @param string ...$tokens The tokens to remove.
     */
    public function remove(string ...$tokens): void
    {
        $removed = false;
        foreach ($tokens as $token) {
            if ('' === $token || 1 === preg_match('/\s/', $token)) {
                throw new InvalidArgumentException(sprintf('Invalid token: "%s"', $token));
            }
            $index = array_search($token, $this->tokens, true);
            if (is_int($index)) {
                array_splice($this->tokens, $index, 1);
                $removed = true;
            }
        }

        if ($removed) {
            $this->syncToOwner();
        }
    }

    /**
     * Replaces an existing token with a new token.
     *
     * @param string $oldToken the token to be replaced
     * @param string $newToken the new token to replace with
     *
     * @return bool true if the replacement was successful, false otherwise
     */
    public function replace(string $oldToken, string $newToken): bool
    {
        if ('' === $oldToken || 1 === preg_match('/\s/', $oldToken)) {
            throw new InvalidArgumentException(sprintf('Invalid old token: "%s"', $oldToken));
        }
        if ('' === $newToken || 1 === preg_match('/\s/', $newToken)) {
            throw new InvalidArgumentException(sprintf('Invalid new token: "%s"', $newToken));
        }

        $index = array_search($oldToken, $this->tokens, true);
        if (is_int($index)) {
            $newIndex = array_search($newToken, $this->tokens, true);
            if (is_int($newIndex)) {
                array_splice($this->tokens, $index, 1);
            } else {
                $this->tokens[$index] = $newToken;
            }
            $this->syncToOwner();

            return true;
        }

        return false;
    }

    /**
     * Toggles a token in the list.
     *
     * @param string    $token the token to toggle
     * @param bool|null $force if true, adds the token; if false, removes it; if null, toggles its presence
     *
     * @return bool true if the token is present after the toggle, false otherwise
     */
    public function toggle(string $token, ?bool $force = null): bool
    {
        if ('' === $token || 1 === preg_match('/\s/', $token)) {
            throw new InvalidArgumentException(sprintf('Invalid token: "%s"', $token));
        }

        $index = array_search($token, $this->tokens, true);
        if (is_int($index)) {
            if (null === $force || false === $force) {
                array_splice($this->tokens, $index, 1);
                $this->syncToOwner();

                return false;
            }

            return true;
        }

        if (null === $force || true === $force) {
            $this->tokens[] = $token;
            $this->syncToOwner();

            return true;
        }

        return false;
    }

    // region implements Countable

    /**
     * Returns the number of tokens in the list.
     *
     * @return int the count of tokens
     */
    public function count(): int
    {
        return count($this->tokens);
    }

    // endregion implements Countable

    // region implements IteratorAggregate

    /**
     * Iterates over the tokens in the list.
     *
     * @return Traversable<int,string> an iterator over the tokens
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->tokens);
    }

    // endregion implements IteratorAggregate

    // region implements Stringable

    /**
     * Returns the string representation of the token list.
     *
     * @return string the tokens joined by a space
     */
    public function __toString(): string
    {
        return implode(' ', $this->tokens);
    }

    // endregion implements Stringable

    // region internal methods

    /**
     * @internal
     * Synchronizes the token list from the owner's attribute
     */
    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner(): void
    {
        $tokens = preg_split('/\s+/', $this->owner->getAttr($this->attrName) ?? '', -1, \PREG_SPLIT_NO_EMPTY);
        assert(is_array($tokens), 'preg_split should return an array');
        $this->tokens = $tokens;
    }

    // endregion internal methods

    private function syncToOwner(): void
    {
        $this->owner->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetAttr($this->attrName, $this->__toString());
    }
}
