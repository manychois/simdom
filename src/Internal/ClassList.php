<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;
use Manychois\Simdom\DOMTokenList;
use Traversable;

class ClassList implements DOMTokenList
{
    public ElementNode $owner;
    /**
     * @var array<string>
     */
    private array $tokens;

    public function __construct(ElementNode $owner)
    {
        $this->owner = $owner;
        $this->reset($owner->getAttribute('class'));
    }

    public function reset(?string $attrValue): void
    {
        if ($attrValue === null) {
            $this->tokens = [];
        } else {
            $tokens = preg_split('/\s+/', $attrValue);
            $notEmpty = function ($s) {
                return $s !== '';
            };
            $this->tokens = array_values(array_unique(array_filter($tokens, $notEmpty)));
        }
    }

    #region implements DOMTokenList properties

    public function count(): int
    {
        return count($this->tokens);
    }

    public function length(): int
    {
        return count($this->tokens);
    }

    public function value(): string
    {
        return implode(' ', $this->tokens);
    }

    #endregion

    #region implements DOMTokenList methods

    public function add(string ...$tokens): void
    {
        $uniq = array_unique($tokens);
        $changed = false;
        foreach ($uniq as $t) {
            $this->validateToken($t);
            if (in_array($t, $this->tokens, true)) {
                continue;
            }
            $this->tokens[] = $t;
            $changed = true;
        }
        if ($changed) {
            $this->updateAttr();
        }
    }

    public function contains(string $token): bool
    {
        $this->validateToken($token);
        return in_array($token, $this->tokens, true);
    }

    /**
     * @return Traversable<string>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->tokens as $token) {
            yield $token;
        }
    }

    public function item(int $index): ?string
    {
        return $this->tokens[$index] ?? null;
    }

    public function remove(string ...$tokens): void
    {
        $uniq = array_unique($tokens);
        $changed = false;
        foreach ($uniq as $t) {
            $this->validateToken($t);
            $i = array_search($t, $this->tokens, true);
            if ($i !== false) {
                array_splice($this->tokens, $i, 1);
                $changed = true;
            }
        }
        if ($changed) {
            $this->updateAttr();
        }
    }

    public function replace(string $old, string $new): bool
    {
        $this->validateToken($old);
        $this->validateToken($new);
        if ($old === $new) {
            return false;
        }
        $i = array_search($old, $this->tokens, true);
        if ($i === false) {
            return false;
        }
        $this->tokens[$i] = $new;
        $this->updateAttr();
        return true;
    }

    public function toggle(string $token, ?bool $force = null): bool
    {
        $this->validateToken($token);
        $i = array_search($token, $this->tokens, true);
        if ($i === false) {
            if ($force !== false) {
                $this->tokens[] = $token;
                $this->updateAttr();
            }
            return true;
        }
        if ($force !== true) {
            array_splice($this->tokens, $i, 1);
            $this->updateAttr();
        }
        return false;
    }

    #endregion

    private function updateAttr(): void
    {
        $this->owner->isInternalAttrChange = true;
        $this->owner->attributes()->set('class', $this->value());
        $this->owner->isInternalAttrChange = false;
    }

    private function validateToken(string $s): void
    {
        if ($s === '') {
            throw new InvalidArgumentException('The token must not be empty.');
        }
        if (preg_match('/\s/', $s)) {
            throw new InvalidArgumentException('The token must not contain any whitespace.');
        }
    }
}
