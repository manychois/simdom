<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Generator;
use InvalidArgumentException;
use Manychois\Cici\DomQuery;
use Manychois\Cici\Exceptions\ParseException;
use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Parsing\SelectorParser;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;
use Manychois\Simdom\Internal\MatchContext;
use Manychois\Simdom\Internal\NodeUtility as Nu;
use Manychois\Simdom\Internal\SelectorListParser;
use OutOfBoundsException;
use Override;

abstract class AbstractParentNode extends AbstractNode
{
    public readonly NodeList $childNodes;

    protected function __construct()
    {
        $this->childNodes = new NodeList($this);
    }

    public HtmlCollection $children {
        get => $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙GetElementList();
    }

    public ?Element $firstElementChild {
        get {
            $found = $this->childNodes->find(static fn(AbstractNode $n) => $n instanceof Element);
            assert($found === null || $found instanceof Element);
            return $found;
        }
    }

    public ?Element $lastElementChild {
        get {
            $found = $this->childNodes->findLast(static fn(AbstractNode $n) => $n instanceof Element);
            assert($found === null || $found instanceof Element);
            return $found;
        }
    }

    final public function append(string|AbstractNode ...$nodes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                throw new InvalidArgumentException('Cannot append a document node');
            }
            if ($node instanceof AbstractParentNode && $node->contains($this)) {
                throw new InvalidArgumentException('Cannot append an ancestor node or itself');
            }
        }
        $nodes = Nu::convertToDistinctNodes(...$nodes);
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append(...$nodes);
    }

    final public function appendChild(AbstractNode $node): void
    {
        if ($node instanceof Document) {
            throw new InvalidArgumentException('Cannot append a document');
        }
        if ($node instanceof AbstractParentNode && $node->contains($this)) {
            throw new InvalidArgumentException('Cannot append an ancestor node or itself');
        }
        if ($node instanceof Fragment) {
            $nodes = Nu::convertToDistinctNodes($node);
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append(...$nodes);
        } else {
            $node->remove();
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($node);
        }
    }

    final public function bfs(callable $predicate): ?AbstractNode
    {
        $queue = [$this];
        while (count($queue) > 0) {
            $current = array_shift($queue);
            if ($predicate($current)) {
                return $current;
            }
            if ($current instanceof AbstractParentNode) {
                $queue = array_merge($queue, $current->childNodes->asArray());
            }
        }

        return null;
    }

    final public function contains(AbstractNode $node): bool
    {
        if ($node === $this) {
            return true; // A node always contains itself
        }

        $current = $node->parent;
        while (null !== $current) {
            if ($current === $this) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * @return Generator<int,AbstractNode>
     */
    final public function descendants(): Generator
    {
        $queue = $this->childNodes->asArray();
        while (count($queue) > 0) {
            $current = array_shift($queue);
            yield $current;
            if ($current instanceof AbstractParentNode) {
                $queue = array_merge($current->childNodes->asArray(), $queue);
            }
        }
    }

    final public function dfs(callable $predicate): ?AbstractNode
    {
        if ($predicate($this)) {
            return $this;
        }
        foreach ($this->descendants() as $d) {
            if ($predicate($d)) {
                return $d;
            }
        }

        return null;
    }

    final public function dfsElement(callable $predicate): ?Element
    {
        $found = $this->dfs(fn(AbstractNode $n) => $n instanceof Element && $predicate($n));
        assert($found === null || $found instanceof Element);
        return $found;
    }

    final public function insertBefore(AbstractNode $node, ?AbstractNode $ref): void
    {
        if (null === $ref) {
            $this->appendChild($node);

            return;
        }
        if ($ref->parent !== $this) {
            throw new InvalidArgumentException('Reference node is not a child of this node.');
        }
        if ($node === $ref) {
            return; // No change needed
        }

        if ($node instanceof Document) {
            throw new InvalidArgumentException('Cannot insert a document');
        }
        if ($node instanceof AbstractParentNode && $node->contains($this)) {
            throw new InvalidArgumentException('Cannot insert an ancestor node or itself');
        }
        if ($node instanceof Fragment) {
            $nodes = Nu::convertToDistinctNodes($node);
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt($ref->index, ...$nodes);
        } else {
            $node->remove();
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt($ref->index, $node);
        }
    }

    final public function normalise(): void
    {
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙normalise();
    }

    final public function prepend(string|AbstractNode ...$nodes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                throw new InvalidArgumentException('Cannot prepend a document node');
            }
            if ($node instanceof AbstractParentNode && $node->contains($this)) {
                throw new InvalidArgumentException('Cannot prepend an ancestor node or itself');
            }
        }
        $nodes = Nu::convertToDistinctNodes(...$nodes);
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt(0, ...$nodes);
    }

    final public function querySelector(string $selector): ?Element
    {
        foreach ($this->querySelectorAll($selector) as $element) {
            return $element; // Return the first matching element
        }

        return null;
    }

    /**
     * Queries the parent node for elements matching the given CSS selector.
     *
     * @param string $selector The CSS selector to match against the descendant elements.
     * @return Generator<int,Element>
     */
    final public function querySelectorAll(string $selector): Generator
    {
        $cssParser = new SelectorListParser();
        $selectorList = $cssParser->parse($selector);
        $context = new MatchContext($this->root, $this, []);
        foreach ($context->loopDescendantElements($this) as $descendant) {
            if ($selectorList->matches($context, $descendant)) {
                yield $descendant;
            }
        }
    }

    final public function removeChild(AbstractNode $node): void
    {
        if ($node->parent !== $this) {
            throw new InvalidArgumentException('Node is not a child of this parent');
        }
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙remove($node);
    }

    final public function replaceChild(AbstractNode $newNode, AbstractNode $oldNode): void
    {
        if ($oldNode->parent !== $this) {
            throw new InvalidArgumentException('Old node is not a child of this parent');
        }
        if ($newNode instanceof Document) {
            throw new InvalidArgumentException('Cannot replace with a document');
        }
        if ($newNode instanceof AbstractParentNode && $newNode->contains($this)) {
            throw new InvalidArgumentException('Cannot replace with an ancestor node or itself');
        }

        if ($newNode === $oldNode) {
            return; // No change needed
        }

        if ($newNode instanceof Fragment) {
            $nodes = Nu::convertToDistinctNodes($newNode);
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙replaceAt($oldNode->index, ...$nodes);
        } else {
            $newNode->remove();
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙replaceAt($oldNode->index, $newNode);
        }
    }

    final public function replaceChildren(string|AbstractNode ...$nodes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                throw new InvalidArgumentException('Cannot replace children with a document node');
            }
            if ($node instanceof AbstractParentNode && $node->contains($this)) {
                throw new InvalidArgumentException('Cannot replace children with an ancestor node or itself');
            }
        }
        $nodes = Nu::convertToDistinctNodes(...$nodes);
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Clear();
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append(...$nodes);
    }

    // region extends AbstractNode

    #[Override]
    public function equals(AbstractNode $other): bool
    {
        if ($this === $other) {
            return true;
        }
        if ($this::class !== $other::class) {
            return false;
        }
        assert($other instanceof AbstractParentNode);

        return $this->childNodes->equals($other->childNodes);
    }

    // endregion extends AbstractNode

    final public function copyChildNodesFrom(AbstractParentNode $other): void
    {
        foreach ($other->childNodes as $node) {
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($node->clone(true));
        }
    }
}
