<?php

namespace Manychois\Simdom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\Internal\DefaultHtmlSerialiser;
use Manychois\Simdom\Internal\MatchContext;
use Stringable;
use Manychois\Simdom\Internal\NodeUtility as Nu;
use Manychois\Simdom\Internal\SelectorListParser;

abstract class AbstractNode implements Stringable
{
    private static ?HtmlSerialiserInterface $defaultSerialiser = null;

    public static function setHtmlSerialiser(HtmlSerialiserInterface $serialiser): void
    {
        self::$defaultSerialiser = $serialiser;
    }

    public static function htmlSerialiser(): HtmlSerialiserInterface
    {
        if (self::$defaultSerialiser === null) {
            self::$defaultSerialiser = new DefaultHtmlSerialiser();
        }
        return self::$defaultSerialiser;
    }

    public private(set) ?AbstractParentNode $parent = null;
    public private(set) int $index = -1;

    final public ?Element $previousElementSibling {
        get {
            $found = $this->parent?->childNodes->findLast(static fn(AbstractNode $node): bool => $node instanceof Element, $this->index - 1);
            assert($found === null || $found instanceof Element);
            return $found;
        }
    }

    final public null|Comment|Doctype|Element|Text $previousSibling {
        get {
            if ($this->parent === null || $this->index <= 0) {
                return null;
            }
            return $this->parent->childNodes->at($this->index - 1);
        }
    }

    final public null|Comment|Doctype|Element|Text $nodeAfter {
        get {
            if ($this instanceof AbstractParentNode) {
                $firstChild = $this->childNodes->at(0);
                if ($firstChild !== null) {
                    return $firstChild;
                }
            }

            $current = $this;
            while ($current !== null) {
                $next = $current->nextSibling;
                if ($next !== null) {
                    return $next;
                }
                $current = $current->parent;
            }

            return null;
        }
    }

    final public ?AbstractNode $nodeBefore {
        get {
            $prev = $this->previousSibling;
            if ($prev === null) {
                return $this->parent;
            }
            while (true) {
                if ($prev instanceof AbstractParentNode) {
                    $last = $prev->childNodes->at(-1);
                    if ($last === null) {
                        break;
                    }
                    $prev = $last;
                } else {
                    break;
                }
            }
            return $prev;
        }
    }

    final public ?Element $nextElementSibling {
        get {
            $found = $this->parent?->childNodes->find(static fn(AbstractNode $node): bool => $node instanceof Element, $this->index + 1);
            assert($found === null || $found instanceof Element);
            return $found;
        }
    }

    final public null|Comment|Doctype|Element|Text $nextSibling {
        get {
            if ($this->parent === null) {
                return null;
            }
            return $this->parent->childNodes->at($this->index + 1);
        }
    }

    final public AbstractNode $root {
        get => $this->parent === null ? $this : $this->parent->root;
    }

    abstract public function clone(bool $deep = true): AbstractNode;

    abstract public function equals(AbstractNode $other): bool;

    public final function after(string|AbstractNode ...$nodes): void
    {
        $parent = $this->parent;
        if ($parent === null) {
            throw new InvalidArgumentException('Cannot insert after a node without a parent');
        }

        $anchor = $this;
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                throw new InvalidArgumentException('Cannot insert a document node');
            }
            if ($node instanceof AbstractParentNode && $node->contains($this)) {
                throw new InvalidArgumentException('Cannot insert an ancestor node or itself');
            }
            if ($node === $anchor) {
                $anchor = $anchor->previousSibling;
            }
        }
        $nodes = Nu::convertToDistinctNodes(...$nodes);
        if ($anchor === null) {
            $parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append(...$nodes);
        } else {
            $parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt($anchor->index + 1, ...$nodes);
        }
    }

    /**
     * @return Generator<int,AbstractParentNode>
     */
    public final function ancestors(): Generator
    {
        $current = $this->parent;
        while ($current !== null) {
            yield $current;
            $current = $current->parent;
        }
    }

    public final function before(string|AbstractNode ...$nodes): void
    {
        $parent = $this->parent;
        if ($parent === null) {
            throw new InvalidArgumentException('Cannot insert before a node without a parent');
        }
        $anchor = $this;
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                throw new InvalidArgumentException('Cannot insert a document node');
            }
            if ($node instanceof AbstractParentNode && $node->contains($this)) {
                throw new InvalidArgumentException('Cannot insert an ancestor node or itself');
            }
            if ($node === $anchor) {
                $anchor = $anchor->nextSibling;
            }
        }
        $nodes = Nu::convertToDistinctNodes(...$nodes);
        if ($anchor === null) {
            $parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append(...$nodes);
        } else {
            $parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt($anchor->index, ...$nodes);
        }
    }

    public final function closest(string $selector): ?Element
    {
        $cssParser = new SelectorListParser();
        $selectorList = $cssParser->parse($selector);
        $context = new MatchContext($this->root, $this, []);
        foreach ($context->loopAncestors($this, true) as $ancestor) {
            if ($ancestor instanceof Element && $selectorList->matches($context, $ancestor)) {
                return $ancestor;
            }
        }

        return null;
    }

    public final function closestFn(callable $predicate): ?Element
    {
        $current = $this;
        while ($current !== null) {
            if ($current instanceof Element && $predicate($current)) {
                return $current;
            }
            $current = $current->parent;
        }
        return null;
    }

    public final function remove(): void
    {
        $this->parent?->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙remove($this);
    }

    public final function replaceWith(string|AbstractNode ...$nodes): void
    {
        if ($this->parent === null) {
            throw new InvalidArgumentException('Cannot replace a node without a parent');
        }
        $hasItself = false;
        $anchor = $this;
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                throw new InvalidArgumentException('Cannot replace with a document node');
            }
            if ($anchor === $node) {
                $anchor = $anchor->previousSibling;
            }
            if ($node === $this) {
                $hasItself = true;
                continue;
            }
            if ($node instanceof AbstractParentNode && $node->contains($this)) {
                throw new InvalidArgumentException('Cannot replace the node with an ancestor node');
            }
        }
        $nodes = Nu::convertToDistinctNodes(...$nodes);
        if ($hasItself) {
            if ($anchor === null) {
                $this->parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append(...$nodes);
            } else {
                $this->parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt($anchor->index + 1, ...$nodes);
            }
        } else {
            $this->parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙replaceAt($this->index, ...$nodes);
        }
    }

    final public function __toString(): string
    {
        return self::htmlSerialiser()->serialise($this);
    }

    // region internal methods

    public final function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetIndex(int $newIndex): void
    {
        $this->index = $newIndex;
    }

    public final function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetParent(?AbstractParentNode $newParent): void
    {
        $this->parent = $newParent;
    }

    // endregion internal methods

    final static protected function validateNoControlCharacters(string $value, string $property): void
    {
        $isMatched = preg_match('/[\x00-\x08\x0E-\x1F\x7F]/', $value, $matches);
        if ($isMatched === 1) {
            throw new InvalidArgumentException(sprintf('%s cannot contain control characters %s', $property, '\\x' . bin2hex($matches[0])));
        }
    }

    final static protected function validateNoWhitespace(string $value, string $property): void
    {
        $isMatched = preg_match('/[\t\n\v\f\r ]/', $value, $matches);
        if ($isMatched === 1) {
            $char = match ($matches[0]) {
                "\t" => 'horizontal tab',
                "\n" => 'line feed',
                "\v" => 'vertical tab',
                "\f" => 'form feed',
                "\r" => 'carriage return',
                default => 'space',
            };
            throw new InvalidArgumentException(sprintf('%s cannot contain %s', $property, $char));
        }
    }

    final static protected function validateNoCharacters(string $value, string $chars, string $property): void
    {
        $isMatched = preg_match('/[' . preg_quote($chars, '/') . ']/', $value, $matches);
        if ($isMatched === 1) {
            throw new InvalidArgumentException(sprintf('%s cannot contain characters (%s)', $property,  $matches[0]));
        }
    }
}
