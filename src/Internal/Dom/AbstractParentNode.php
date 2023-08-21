<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Generator;
use Manychois\Simdom\NodeInterface;
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

    /**
     * Append a child node to this node without any checks.
     *
     * @param AbstractNode $node The node to append.
     */
    public function fastAppend(AbstractNode $node): void
    {
        if ($node->parentNode() !== null) {
            $node->parentNode()->removeChild($node);
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

        $this->cNodes[] = $node;
    }

    #region implements ParentNodeInterface

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
    public function firstChild(): ?NodeInterface
    {
        return $this->cNodes[0] ?? null;
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
    public function removeChild(NodeInterface $node): bool
    {
        $index = array_search($node, $this->cNodes, true);
        if ($index === false) {
            return false;
        }
        array_splice($this->cNodes, $index, 1);

        return true;
    }

    #endregion
}
