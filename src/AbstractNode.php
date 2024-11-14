<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\AbstractParentNode;

/**
 * Represents a node in the DOM tree.
 */
abstract class AbstractNode
{
    protected ?AbstractParentNode $owner = null;
    protected int $index = -1;

    /**
     * Removes duplicates from the list.
     * This differs from the array_unique function in that it removes the first occurrence of a duplicate.
     *
     * @param string|self ...$nodes The nodes to process.
     *
     * @return array<int,string|self> A list of unique nodes.
     */
    protected static function uniqueNodes(string|self ...$nodes): array
    {
        $result = [];
        foreach ($nodes as $node) {
            if (!\is_string($node)) {
                $i = \array_search($node, $result, true);
                if (\is_int($i)) {
                    \array_splice($result, $i, 1);
                }
            }
            $result[] = $node;
        }

        return $result;
    }

    /**
     * Removes the given nodes from their parents.
     *
     * @param string|AbstractNode ...$nodes The nodes to detach from their parents.
     */
    protected static function detachAll(string|self ...$nodes): void
    {
        $groupByParent = [];
        foreach ($nodes as $node) {
            if (!($node instanceof self)) {
                continue;
            }

            $parent = $node->owner;
            if ($parent === null) {
                continue;
            }

            $parentId = \spl_object_id($parent);
            if (!isset($groupByParent[$parentId])) {
                $groupByParent[$parentId] = [
                    'nodes' => [],
                    'parent' => $parent,
                ];
            }
            $groupByParent[$parentId]['nodes'][] = $node;
        }

        foreach ($groupByParent as $group) {
            $group['parent']->remove(...$group['nodes']);
        }
    }

    /**
     * Gets the concatenated text data of this node and its descendants, if any.
     *
     * @param array<string> $excludes The element names to exclude.
     *
     * @return string The concatenated text data of this node and its descendants, if any.
     */
    abstract public function allTextData(array $excludes = ['head', 'script', 'style', 'template']): string;

    /**
     * Returns a duplicate of this node.
     *
     * @param bool $deep Whether to clone the descendants of this node.
     *
     * @return self The cloned node.
     */
    abstract public function clone(bool $deep): self;

    /**
     * Checks if this node equals to the given node.
     * Two nodes are equal when they have the same type and defining characteristics
     * (for elements, this would be their ID, number of children, and so forth).
     *
     * @param self|null $node The node to compare.
     *
     * @return bool True if this node equals to the given node, false otherwise.
     */
    abstract public function equals(?self $node): bool;

    /**
     * Gets the type of this node.
     */
    abstract public function nodeType(): NodeType;

    /**
     * Converts this node to an HTML string.
     *
     * @return string The HTML string representation of this node.
     */
    abstract public function toHtml(): string;

    /**
     * Inserts the given nodes in the child node list of this node's parent, just after this node.
     *
     * @param self|string ...$nodes The nodes to insert. If a string is given, it will be converted to a text node.
     */
    public function after(string|self ...$nodes): void
    {
        if ($this->owner === null) {
            throw new \InvalidArgumentException('This node has no parent.');
        }

        $viableNextSibling = $this->nextSibling();
        while (\in_array($viableNextSibling, $nodes, true)) {
            $viableNextSibling = $viableNextSibling->nextSibling();
        }

        $this->owner->insertBefore($viableNextSibling, ...$nodes);
    }

    /**
     * Iterates from this node's parent to the root node.
     *
     * @return \Generator<int,Document|Element> The ancestors of this node.
     */
    public function ancestors(): \Generator
    {
        $node = $this->owner;
        while ($node !== null) {
            \assert($node instanceof Document || $node instanceof Element);

            yield $node;

            $node = $node->owner;
        }
    }

    /**
     * Inserts the given nodes in the child node list of this node's parent, just before this node.
     *
     * @param string|AbstractNode ...$nodes The nodes to insert.
     *                                      If a string is given, it will be converted to a text node.
     */
    public function before(string|self ...$nodes): void
    {
        if ($this->owner === null) {
            throw new \InvalidArgumentException('This node has no parent.');
        }
        $this->owner->insertBefore($this, ...$nodes);
    }

    /**
     * Checks if the given node equals to or is a descendant of this node.
     *
     * @param self|null $node The node to check.
     *
     * @return bool Whether the given node equals to or is a descendant of this node.
     */
    public function contains(?self $node): bool
    {
        if ($this instanceof Comment || $this instanceof Text) {
            return $this === $node;
        }

        $n = $node;
        while ($n !== null) {
            if ($n === $this) {
                return true;
            }
            $n = $n->owner;
        }

        return false;
    }

    /**
     * Removes this node from its parent, if any.
     */
    public function detach(): void
    {
        $this->owner?->remove($this);
    }

    /**
     * Returns the index of this node in its parent's child node list.
     * If this node has no parent, -1 is returned.
     *
     * @return int The index of this node in its parent's child node list, or -1 if this node has no parent.
     */
    public function index(): int
    {
        return $this->index;
    }

    /**
     * Checks if this node comes after the given node in the node tree.
     *
     * @param AbstractNode $node The node to check.
     *
     * @return bool True if this node comes after the given node in the node tree, false otherwise.
     */
    public function isFollowing(self $node): bool
    {
        if ($node === $this || $this->owner === null || $node->owner === null) {
            return false;
        }
        if ($this->owner === $node->owner) {
            foreach ($this->owner->childNodeList as $n) {
                if ($n === $node) {
                    return true;
                }
            }

            return false;
        }

        return $node->contains($this);
    }

    /**
     * Checks if this node comes before the given node in the node tree.
     *
     * @param AbstractNode $node The node to check.
     *
     * @return bool True if this node comes before the given node in the node tree, false otherwise.
     */
    public function isPreceding(self $node): bool
    {
        if ($node === $this || $this->owner === null || $node->owner === null) {
            return false;
        }
        if ($this->owner === $node->owner) {
            foreach ($this->owner->childNodeList as $n) {
                if ($n === $this) {
                    return true;
                }
            }

            return false;
        }

        return $this->contains($node);
    }

    /**
     * Gets the first sibling element which follows this node, if any.
     *
     * @return Element|null The first sibling element which follows this node, or null if there is none.
     */
    public function nextElementSibling(): ?Element
    {
        if ($this->owner === null) {
            return null;
        }

        $childNodeList = $this->owner->childNodeList;
        $n = $childNodeList->count();
        for ($i = $this->index + 1; $i < $n; ++$i) {
            $node = $childNodeList->get($i);
            if ($node instanceof Element) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the next sibling of this node.
     *
     * @return self|null The next sibling of this node, or null if there is none.
     */
    public function nextSibling(): ?self
    {
        if ($this->owner === null) {
            return null;
        }

        return $this->owner->childNodeList->get($this->index + 1);
    }

    /**
     * Gets the parent node of this node.
     *
     * @return Document|Element|null The parent node of this node, or null if there is none.
     */
    public function parent(): Document|Element|null
    {
        \assert($this->owner === null || $this->owner instanceof Document || $this->owner instanceof Element);

        return $this->owner;
    }

    /**
     * Gets the parent element of this node.
     *
     * @return Element|null The parent element of this node, or null if there is none.
     */
    public function parentElement(): ?Element
    {
        return $this->owner instanceof Element ? $this->owner : null;
    }

    /**
     * Gets the previous sibling element which precedes this node, if any.
     *
     * @return Element|null The previous sibling element which precedes this node, or null if there is none.
     */
    public function prevElementSibling(): ?Element
    {
        if ($this->owner === null) {
            return null;
        }

        $childNodeList = $this->owner->childNodeList;
        for ($i = $this->index - 1; $i >= 0; --$i) {
            $node = $childNodeList->get($i);
            if ($node instanceof Element) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the previous sibling of this node.
     *
     * @return self|null The previous sibling of this node, or null if there is none.
     */
    public function prevSibling(): ?self
    {
        if ($this->owner === null) {
            return null;
        }

        return $this->owner->childNodeList->get($this->index - 1);
    }

    /**
     * Gets the topmost node in the tree that contains this node.
     * It can be this node itself if the node has no parent.
     *
     * @return self The topmost node in the tree that contains this node.
     */
    public function root(): self
    {
        $node = $this;
        while ($node->owner !== null) {
            $node = $node->owner;
        }

        return $node;
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    #region Internal methods

    /**
     * Sets the index of this node in its parent's child node list.
     *
     * @param int $index The index of this node in its parent's child node list.
     *
     * @internal
     */
    public function 🚫setIndex(int $index): void
    {
        $this->index = $index;
    }

    /**
     * Sets the owner of this node.
     *
     * @param AbstractParentNode|null $owner The owner of this node.
     *
     * @internal
     */
    public function 🚫setOwner(?AbstractParentNode $owner): void
    {
        $this->owner = $owner;
    }

    #endregion Internal methods
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
}
