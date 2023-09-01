<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\DocumentFragmentInterface;
use Manychois\Simdom\DocumentInterface;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\NodeInterface;
use Manychois\Simdom\ParentNodeInterface;
use Manychois\Simdom\TextInterface;

/**
 * Internal implementation of ParentNodeInterface.
 */
abstract class AbstractParentNode extends AbstractNode implements ParentNodeInterface
{
    /**
     * @var array<int, AbstractNode>
     */
    protected array $cNodes = [];

    #region implements ParentNodeInterface

    /**
     * @inheritdoc
     */
    public function append(string|NodeInterface ...$nodes): void
    {
        $nodes = static::flattenNodes(...$nodes);
        $this->validatePreInsertion($nodes, null);
        foreach ($nodes as $node) {
            if ($node->pNode !== null) {
                $node->pNode->removeChild($node);
            }
            $node->pNode = $this;
            $this->cNodes[] = $node;
        }
    }

    /**
     * @inheritdoc
     */
    public function childElementCount(): int
    {
        $count = 0;
        foreach ($this->cNodes as $node) {
            if ($node instanceof ElementInterface) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @inheritdoc
     */
    public function childNodeAt(int $index): ?NodeInterface
    {
        return $this->cNodes[$index] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function childNodeCount(): int
    {
        return count($this->cNodes);
    }

    /**
     * @inheritdoc
     */
    public function childNodes(): Generator
    {
        foreach ($this->cNodes as $node) {
            yield $node;
        }
    }

    /**
     * @inheritdoc
     */
    public function clear(): void
    {
        foreach ($this->cNodes as $node) {
            $node->pNode = null;
        }
        $this->cNodes = [];
    }

    /**
     * @inheritdoc
     */
    public function contains(NodeInterface $node): bool
    {
        if ($node === $this) {
            return true;
        }
        $parent = $node->parentNode();
        while ($parent !== null) {
            if ($parent === $this) {
                return true;
            }
            $parent = $parent->parentNode();
        }

        return false;
    }


    /**
     * @inheritdoc
     */
    public function find(callable $predicate): ?NodeInterface
    {
        foreach ($this->cNodes as $i => $node) {
            if ($predicate($node, $i)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function findIndex(callable $predicate): int
    {
        foreach ($this->cNodes as $i => $node) {
            if ($predicate($node, $i)) {
                assert($i >= 0, "Invalid index $i");

                return $i;
            }
        }

        return -1;
    }

    /**
     * @inheritdoc
     */
    public function firstChild(): ?NodeInterface
    {
        return $this->cNodes[0] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function firstElementChild(): ?ElementInterface
    {
        foreach ($this->cNodes as $node) {
            if ($node instanceof ElementInterface) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function insertBefore(?NodeInterface $ref, string|NodeInterface ...$nodes): void
    {
        assert($ref === null || $ref instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
        $nodes = static::flattenNodes(...$nodes);
        $this->validatePreInsertion($nodes, $ref);
        foreach ($nodes as $node) {
            if ($node->pNode !== null) {
                $node->pNode->removeChild($node);
            }
            $node->pNode = $this;
        }
        if ($ref === null) {
            foreach ($nodes as $node) {
                $this->cNodes[] = $node;
            }
        } else {
            $index = array_search($ref, $this->cNodes, true);
            assert(is_int($index), 'validatePreInsertion() should have thrown an exception.');
            array_splice($this->cNodes, $index, 0, $nodes);
        }
    }

    /**
     * @inheritdoc
     */
    public function lastChild(): ?NodeInterface
    {
        $last = end($this->cNodes);

        return $last === false ? null : $last;
    }

    /**
     * @inheritdoc
     */
    public function lastElementChild(): ?ElementInterface
    {
        for ($i = count($this->cNodes) - 1; $i >= 0; --$i) {
            $node = $this->cNodes[$i];
            if ($node instanceof ElementInterface) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function prepend(string|NodeInterface ...$nodes): void
    {
        $nodes = static::flattenNodes(...$nodes);
        $this->validatePreInsertion($nodes, $this->cNodes[0] ?? null);
        foreach ($nodes as $node) {
            if ($node->pNode !== null) {
                $node->pNode->removeChild($node);
            }
            $node->pNode = $this;
        }
        array_splice($this->cNodes, 0, 0, $nodes);
    }

    /**
     * @inheritdoc
     */
    public function removeChild(NodeInterface $node): bool
    {
        $index = array_search($node, $this->cNodes, true);
        if ($index === false) {
            return false;
        }
        assert($node instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
        $node->pNode = null;
        array_splice($this->cNodes, $index, 1);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function replace(NodeInterface $old, string|NodeInterface ...$newNodes): void
    {
        assert($old instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
        $newNodes = static::flattenNodes(...$newNodes);
        $replaceAt = $this->validatePreReplace($old, $newNodes);
        $old->pNode = null;
        foreach ($newNodes as $node) {
            if ($node->pNode !== null) {
                $node->pNode->removeChild($node);
            }
            $node->pNode = $this;
        }
        array_splice($this->cNodes, $replaceAt, 1, $newNodes);
    }

    #endregion

    #region extends AbstractNode

    /**
     * @inheritdoc
     */
    public function toHtml(): string
    {
        $html = '';
        foreach ($this->childNodes() as $child) {
            $html .= $child->toHtml();
        }

        return $html;
    }

    #endregion

    /**
     * Appends a child node to this node without any checks.
     * The child node will be removed from its original parent node first if it has one.
     * If both the new child node and the existing last child node are Text nodes, they will be merged into one.
     * This method should be used internally by `DomParser` only.
     *
     * @param AbstractNode $node The node to append.
     */
    public function fastAppend(AbstractNode $node): void
    {
        if ($node->pNode !== null) {
            $node->pNode->removeChild($node);
        }
        if ($node instanceof TextInterface) {
            if ($node->data() === '') {
                return;
            }

            $last = end($this->cNodes);
            if ($last instanceof TextInterface) {
                $last->setData($last->data() . $node->data());

                return;
            }
        }

        $node->pNode = $this;
        $this->cNodes[] = $node;
    }

    /**
     * Flattens the specified nodes into a single array.
     *
     * @param string|NodeInterface ...$nodes Nodes to be flattened.
     *                                       String will be converted into Text.
     *                                       DocumentFragment nodes are expanded into their child nodes.
     *
     * @return array<AbstractNode> The flattened nodes. Note that they are still connected to their original parents.
     */
    protected static function flattenNodes(string|NodeInterface ...$nodes): array
    {
        $flattened = [];
        foreach ($nodes as $node) {
            if (is_string($node)) {
                $flattened[] = new TextNode($node);
            } else {
                if ($node instanceof DocumentFragmentInterface) {
                    foreach ($node->childNodes() as $child) {
                        assert($child instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
                        $index = array_search($child, $flattened, true);
                        if ($index !== false) {
                            array_splice($flattened, $index, 1);
                        }
                        $flattened[] = $child;
                    }
                } else {
                    assert($node instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
                    $index = array_search($node, $flattened, true);
                    if ($index !== false) {
                        array_splice($flattened, $index, 1);
                    }
                    $flattened[] = $node;
                }
            }
        }

        return $flattened;
    }

    /**
     * Validates if the specified nodes can be inserted into this node, at the position before the reference node.
     * `InvalidArgumentException` is thrown if the validation fails.
     *
     * @param array<AbstractNode> $nodes Nodes to be inserted. Fragment nodes should not be included, and should include
     *                                   their child nodes instead.
     * @param null|AbstractNode   $ref   The reference node, or null to indicate the end of the child node list.
     */
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): void
    {
        if ($ref !== null && !$this->contains($ref)) {
            throw new InvalidArgumentException('The reference child is not found in the parent node.');
        }
        foreach ($nodes as $node) {
            if ($node instanceof self) {
                if ($node === $this) {
                    throw new InvalidArgumentException('A node cannot be its own child.');
                }
                if ($node->contains($this)) {
                    throw new InvalidArgumentException('A child node cannot contain its own ancestor.');
                }
                if ($node instanceof DocumentInterface) {
                    throw new InvalidArgumentException('A document cannot be a child of any node.');
                }
            }
        }
    }

    /**
     * Validates if the existing node can be replaced by the specified nodes.
     * `InvalidArgumentException` is thrown if the validation fails.
     *
     * @param AbstractNode        $old      The existing node to be replaced.
     * @param array<AbstractNode> $newNodes The nodes to replace the existing node. Fragment nodes should not be
     *                                      included, and should include their child nodes instead.
     *
     * @return int<0, max> The index of the node to be replaced.
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        $index = $this->findIndex(fn ($n) => $n === $old);
        if ($index < 0) {
            throw new InvalidArgumentException('The node to be replaced is not found in the parent node.');
        }
        foreach ($newNodes as $new) {
            if ($new instanceof ParentNodeInterface) {
                if ($new === $this) {
                    throw new InvalidArgumentException('A node cannot be its own child.');
                }
                if ($new->contains($this)) {
                    throw new InvalidArgumentException('A child node cannot contain its own ancestor.');
                }
                if ($new instanceof DocumentInterface) {
                    throw new InvalidArgumentException('A document cannot be a child of another node.');
                }
            }
        }

        return $index;
    }
}
