<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\Internal\MatchContext;
use Manychois\Simdom\Internal\NodeUtility as Nu;
use Manychois\Simdom\Internal\SelectorListParser;
use Override;

/**
 * Represents a parent node in the DOM tree.
 */
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

    public ?AbstractNode $firstChild {
        get => $this->childNodes->at(0);
    }

    public ?Element $firstElementChild {
        get {
            $found = $this->childNodes->find(static fn (AbstractNode $n) => $n instanceof Element);
            assert(null === $found || $found instanceof Element);

            return $found;
        }
    }

    public ?AbstractNode $lastChild {
        get => $this->childNodes->at(-1);
    }

    public ?Element $lastElementChild {
        get {
            $found = $this->childNodes->findLast(static fn (AbstractNode $n) => $n instanceof Element);
            assert(null === $found || $found instanceof Element);

            return $found;
        }
    }

    /**
     * Appends nodes or strings to the end of the child nodes.
     *
     * @param string|AbstractNode ...$nodes The nodes or strings to append.
     */
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

    /**
     * Appends a child node to the end of the child nodes.
     *
     * @param AbstractNode $node the child node to append
     */
    final public function appendChild(AbstractNode $node): void
    {
        if ($node instanceof Document) {
            throw new InvalidArgumentException('Cannot append a document node');
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

    /**
     * Performs a breadth-first search (BFS) on the node tree.
     *
     * @param callable $predicate the predicate function to test each node
     *
     * @return AbstractNode|null the first node that satisfies the predicate, or null if none found
     */
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

    /**
     * Determines whether this node contains the specified node.
     * A node is considered to contain itself.
     *
     * @param AbstractNode $node the node to check
     *
     * @return bool true if this node contains the specified node, false otherwise
     */
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
     * Yields all descendant nodes of this node.
     *
     * @return Generator<int,AbstractNode> the descendant nodes
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

    /**
     * Yields all descendant elements of this node.
     *
     * @return Generator<int,Element> the descendant elements
     */
    final public function descendantElements(): Generator
    {
        foreach ($this->descendants() as $d) {
            if ($d instanceof Element) {
                yield $d;
            }
        }
    }

    /**
     * Performs a depth-first search (DFS) on the node tree.
     * This is the same as the document order.
     *
     * @param callable $predicate the predicate function to test each node
     *
     * @return AbstractNode|null the first node that satisfies the predicate, or null if none found
     */
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

    /**
     * Performs a depth-first search (DFS) on the node tree. Only descendant elements are considered.
     *
     * @param callable $predicate the predicate function to test each element
     *
     * @return Element|null the first element that satisfies the predicate, or null if none found
     */
    final public function dfsElement(callable $predicate): ?Element
    {
        $found = $this->dfs(fn (AbstractNode $n) => $n instanceof Element && $predicate($n));
        assert(null === $found || $found instanceof Element);

        return $found;
    }

    /**
     * Inserts a node before the reference node.
     *
     * @param AbstractNode      $node the node to insert
     * @param AbstractNode|null $ref  the reference node before which the new node will be inserted
     */
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

    /**
     * Normalises the node and its descendants.
     * This merges adjacent text nodes and removes empty text nodes.
     */
    final public function normalise(): void
    {
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙normalise();
        foreach ($this->childNodes as $node) {
            if ($node instanceof AbstractParentNode) {
                $node->normalise();
            }
        }
    }

    /**
     * Inserts nodes at the beginning of the child nodes.
     *
     * @param string|AbstractNode ...$nodes The nodes to prepend.
     */
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

    /**
     * Queries the parent node for the first element matching the given CSS selector.
     *
     * @param string $selector the CSS selector to match against the descendant elements
     *
     * @return Element|null the first matching element, or null if none found
     */
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
     * @param string $selector the CSS selector to match against the descendant elements
     *
     * @return Generator<int,Element> the matching elements
     */
    final public function querySelectorAll(string $selector): Generator
    {
        $cssParser = new SelectorListParser();
        $selectorList = $cssParser->parse($selector);
        $context = new MatchContext($this->root, $this, []);
        foreach ($context->loopDescendantElements($this) as $descendant) {
            if ($selectorList->matches($context, $descendant)) {
                assert($descendant instanceof Element);
                yield $descendant;
            }
        }
    }

    /**
     * Removes a child node from this parent.
     *
     * @param AbstractNode $node the child node to remove
     */
    final public function removeChild(AbstractNode $node): void
    {
        if ($node->parent !== $this) {
            throw new InvalidArgumentException('Node is not a child of this parent');
        }
        $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Remove($node);
    }

    /**
     * Replaces a child node with a new node.
     *
     * @param AbstractNode $newNode the new node to insert
     * @param AbstractNode $oldNode the old node to be replaced
     */
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
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙ReplaceAt($oldNode->index, ...$nodes);
        } else {
            $newNode->remove();
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙ReplaceAt($oldNode->index, $newNode);
        }
    }

    /**
     * Replaces the children of this parent with the given nodes.
     *
     * @param string|AbstractNode ...$nodes The new children to set.
     */
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

    final protected function copyChildNodesFrom(AbstractParentNode $other): void
    {
        foreach ($other->childNodes as $node) {
            $this->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($node->clone(true));
        }
    }
}
