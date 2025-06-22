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

    public function __construct(Element $owner, string $attrName)
    {
        $this->owner = $owner;
        $this->attrName = $attrName;
        $this->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner();
    }

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

    public function contains(string $token): bool
    {
        if ($token === '' || 1 === preg_match('/\s/', $token)) {
            throw new InvalidArgumentException(sprintf('Invalid token: "%s"', $token));
        }

        return in_array($token, $this->tokens, true);
    }

    public function remove(string ...$tokens): void
    {
        $removed = false;
        foreach ($tokens as $token) {
            if ($token === '' || 1 === preg_match('/\s/', $token)) {
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

    public function replace(string $oldToken, string $newToken): bool
    {
        if ($oldToken === '' || 1 === preg_match('/\s/', $oldToken)) {
            throw new InvalidArgumentException(sprintf('Invalid old token: "%s"', $oldToken));
        }
        if ($newToken === '' || 1 === preg_match('/\s/', $newToken)) {
            throw new InvalidArgumentException(sprintf('Invalid new token: "%s"', $newToken));
        }

        $index = array_search($oldToken, $this->tokens, true);
        if (is_int($index)) {
            $this->tokens[$index] = $newToken;
            $this->syncToOwner();

            return true;
        }

        return false;
    }

    public function toggle(string $token, ?bool $force = null): bool
    {
        if ($token === '' || 1 === preg_match('/\s/', $token)) {
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

    public function count(): int
    {
        return count($this->tokens);
    }

    // endregion implements Countable

    // region implements IteratorAggregate

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->tokens);
    }

    // endregion implements IteratorAggregate

    // region implements Stringable

    public function __toString(): string
    {
        return implode(' ', $this->tokens);
    }

    // endregion implements Stringable

    // region internal methods

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
