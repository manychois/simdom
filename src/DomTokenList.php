<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a set of space-separated tokens.
 *
 * @template-implements \IteratorAggregate<int,string>
 */
class DomTokenList implements \Countable, \IteratorAggregate, \Stringable
{
    private readonly Element $owner;
    private readonly string $attrName;
    /**
     * @var array<int,string>
     */
    private array $tokens = [];
    private bool $synced = false;

    /**
     * Constructs a new instance of this class.
     *
     * @param Element $owner    The element that owns this token list.
     * @param string  $attrName The lowercased name of the attribute that stores the tokens.
     */
    public function __construct(Element $owner, string $attrName)
    {
        $this->owner = $owner;
        $this->attrName = $attrName;
        $this->syncFromElement();
    }

    /**
     * Adds the specified tokens to the list.
     *
     * @param string ...$tokens The tokens to add.
     */
    public function add(string ...$tokens): void
    {
        foreach ($tokens as $token) {
            if ($token === '') {
                throw new \InvalidArgumentException('The token cannot be empty.');
            }

            if (\str_contains($token, ' ')) {
                throw new \InvalidArgumentException('The token cannot contain a space.');
            }
        }

        if (\count($tokens) === 0) {
            return;
        }
        $this->syncFromElement();
        /** @var array<int,string> $tokens */
        $tokens = \array_unique(\array_merge($this->tokens, $tokens));
        $this->tokens = $tokens;
        $this->syncToElement();
    }

    /**
     * Checks if the specified token is in the list.
     *
     * @param string $token The token to check.
     *
     * @return bool True if the token is in the list, false otherwise.
     */
    public function contains(string $token): bool
    {
        if (\str_contains($token, ' ')) {
            throw new \InvalidArgumentException('The token cannot contain a space.');
        }
        $this->syncFromElement();

        return \in_array($token, $this->tokens, true);
    }

    /**
     * Gets the token at the specified index.
     *
     * @param int $index The zero-based index of the token to get.
     *
     * @return string|null The token at the specified index, or null if the index is out of range.
     */
    public function item(int $index): ?string
    {
        $this->syncFromElement();

        return $this->tokens[$index] ?? null;
    }

    /**
     * Removes the specified tokens from the list.
     *
     * @param string ...$tokens The tokens to remove.
     */
    public function remove(string ...$tokens): void
    {
        foreach ($tokens as $token) {
            if ($token === '') {
                throw new \InvalidArgumentException('The token cannot be empty.');
            }

            if (\str_contains($token, ' ')) {
                throw new \InvalidArgumentException('The token cannot contain a space.');
            }
        }

        if (\count($tokens) === 0) {
            return;
        }
        $this->syncFromElement();
        $this->tokens = \array_diff($this->tokens, $tokens);
        $this->syncToElement();
    }

    /**
     * Replaces the old token with the new token.
     *
     * @param string $oldToken The token to replace.
     * @param string $newToken The token to replace with.
     *
     * @return bool True if the old token is found, false otherwise.
     */
    public function replace(string $oldToken, string $newToken): bool
    {
        if ($oldToken === '') {
            throw new \InvalidArgumentException('The old token cannot be empty.');
        }

        if (\str_contains($oldToken, ' ')) {
            throw new \InvalidArgumentException('The old token cannot contain a space.');
        }
        if ($newToken === '') {
            throw new \InvalidArgumentException('The new token cannot be empty.');
        }

        if (\str_contains($newToken, ' ')) {
            throw new \InvalidArgumentException('The new token cannot contain a space.');
        }

        $this->syncFromElement();
        $index = \array_search($oldToken, $this->tokens, true);
        if (!\is_int($index)) {
            return false;
        }
        $this->tokens[$index] = $newToken;
        $this->syncToElement();

        return true;
    }

    /**
     * Toggles the specified token in the list.
     *
     * @param string    $token The token to toggle.
     * @param bool|null $force If true, adds the token; if false, removes the token.
     *
     * @return bool True if the token exists after the operation, false otherwise.
     */
    public function toogle(string $token, ?bool $force = null): bool
    {
        if ($token === '') {
            throw new \InvalidArgumentException('The token cannot be empty.');
        }

        if (\str_contains($token, ' ')) {
            throw new \InvalidArgumentException('The token cannot contain a space.');
        }

        $this->syncFromElement();
        $index = \array_search($token, $this->tokens, true);
        if (\is_int($index)) {
            $exists = true;
            if ($force !== true) {
                \array_splice($this->tokens, $index, 1);
                $this->syncToElement();
            }
        } else {
            $exists = false;
            if ($force !== false) {
                $this->tokens[] = $token;
                $this->syncToElement();
            }
        }

        return $exists;
    }

    /**
     * Gets the value of the list as a string.
     *
     * @return string The value of the list as a string.
     */
    public function value(): string
    {
        $this->syncFromElement();

        return \implode(' ', $this->tokens);
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    #region Internal methods

    /**
     * Marks this token list as out of sync with the attribute value.
     *
     * @internal
     */
    public function 🚫markOutOfSync(): void
    {
        $this->synced = false;
    }

    #endregion Internal methods
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps


    #region implements \Countable

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        $this->syncFromElement();

        return \count($this->tokens);
    }

    #endregion implements \Countable

    #region implements \IteratorAggregate

    /**
     * @inheritDoc
     */
    public function getIterator(): \Traversable
    {
        $this->syncFromElement();

        foreach ($this->tokens as $token) {
            yield $token;
        }
    }

    #endregion implements \IteratorAggregate

    #region implements \Stringable

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->value();
    }

    #endregion implements \Stringable

    /**
     * Synchonizes the tokens in the list with the attribute value.
     */
    private function syncFromElement(): void
    {
        if ($this->synced) {
            return;
        }
        $value = $this->owner->getAttr($this->attrName) ?? '';
        /** @var array<int,string> $tokens */
        $tokens = \preg_split('/\s+/', $value, -1, \PREG_SPLIT_NO_EMPTY);
        $this->tokens = \array_unique($tokens);
        $this->synced = true;
    }

    /**
     * Synchonizes the attribute value with the tokens in the list.
     */
    private function syncToElement(): void
    {
        $this->owner->🚫setAttr($this->attrName, $this->value());
    }
}
