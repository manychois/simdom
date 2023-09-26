<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\DocumentInterface;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\Css\SelectorParser;
use Manychois\Simdom\NodeInterface;
use Manychois\Simdom\NodeListInterface;
use Manychois\Simdom\ParentNodeInterface;
use Manychois\Simdom\TextInterface;

/**
 * Internal implementation of ParentNodeInterface.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractParentNode extends AbstractNode implements ParentNodeInterface
{
    public readonly LiveNodeList $cNodes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cNodes = new LiveNodeList();
    }

    #region implements ParentNodeInterface

    /**
     * @inheritDoc
     */
    public function append(string|NodeInterface ...$nodes): void
    {
        $this->insertBefore(null, ...$nodes);
    }

    /**
     * @inheritDoc
     */
    public function childNodes(): NodeListInterface
    {
        return $this->cNodes;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        foreach ($this->cNodes as $node) {
            assert($node instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
            $node->pNode = null;
        }
        $this->cNodes->clear();
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function descendantNodes(): Generator
    {
        $idx = 0;
        foreach ($this->cNodes as $node) {
            yield $idx => $node;
            ++$idx;
            if ($node instanceof ParentNodeInterface) {
                foreach ($node->descendantNodes() as $descendant) {
                    yield $idx => $descendant;
                    ++$idx;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function descendantElements(): Generator
    {
        $idx = 0;
        foreach ($this->descendantNodes() as $node) {
            if ($node instanceof ElementInterface) {
                yield $idx => $node;
                ++$idx;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function insertBefore(?NodeInterface $ref, string|NodeInterface ...$nodes): void
    {
        assert($ref instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
        $nodes = NodeFlattener::flattenNodes(...$nodes);
        $index = $this->validatePreInsertion($nodes, $ref);
        foreach ($nodes as $node) {
            $node->pNode?->removeChild($node);
            $node->pNode = $this;
        }
        $this->cNodes->splice($index, 0, $nodes);
    }

    /**
     * @inheritDoc
     */
    public function querySelector(string $selector): ?ElementInterface
    {
        foreach ($this->querySelectorAll($selector) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function querySelectorAll(string $selector): Generator
    {
        $cssParser = new SelectorParser();
        $selector = $cssParser->parse($selector);
        foreach ($this->descendantElements() as $element) {
            if ($selector->matchWith($element)) {
                yield $element;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function removeChild(NodeInterface $node): bool
    {
        $index = $this->cNodes->indexOf($node);
        if ($index < 0) {
            return false;
        }

        assert($node instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
        $node->pNode = null;
        $this->cNodes->splice($index, 1);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function replace(NodeInterface $old, string|NodeInterface ...$newNodes): void
    {
        assert($old instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
        $newNodes = NodeFlattener::flattenNodes(...$newNodes);
        $replaceAt = $this->validatePreReplace($old, $newNodes);
        $old->pNode = null;
        foreach ($newNodes as $node) {
            if ($node->pNode !== null) {
                $node->pNode->removeChild($node);
            }
            $node->pNode = $this;
        }
        $this->cNodes->splice($replaceAt, 1, $newNodes);
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
        $node->pNode?->removeChild($node);
        if ($node instanceof TextInterface) {
            if ($node->data() === '') {
                return;
            }

            $last = $this->cNodes->nodeAt(-1);
            if ($last instanceof TextInterface) {
                $last->setData($last->data() . $node->data());

                return;
            }
        }

        $node->pNode = $this;
        $this->cNodes->append($node);
    }

    /**
     * Validates if the specified nodes can be inserted into this node, at the position before the reference node.
     * `InvalidArgumentException` is thrown if the validation fails.
     *
     * @param array<AbstractNode> $nodes Nodes to be inserted. Fragment nodes should not be included, and should include
     *                                   their child nodes instead.
     * @param null|AbstractNode   $ref   The reference node, or null to indicate the end of the child node list.
     *
     * @return int The index to insert the nodes.
     *
     * @psalm-return non-negative-int
     */
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): int
    {
        if ($ref === null) {
            $index = $this->cNodes->count();
        } else {
            $index = $this->cNodes->indexOf($ref);
        }
        if ($index < 0) {
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

        return $index;
    }

    /**
     * Validates if the existing node can be replaced by the specified nodes.
     * `InvalidArgumentException` is thrown if the validation fails.
     *
     * @param AbstractNode   $old      The existing node to be replaced.
     * @param AbstractNode[] $newNodes The nodes to replace the existing node. Fragment nodes should not be
     *                                 included, and should include their child nodes instead.
     *
     * @return int The index of the node to be replaced.
     *
     * @psalm-return non-negative-int
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        $index = $this->cNodes->indexOf($old);
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
