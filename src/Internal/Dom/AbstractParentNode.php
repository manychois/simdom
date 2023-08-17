<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\NodeInterface;

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
        if ($node->parentNode()) {
            $node->parentNode()->removeChild($node);
        }
        $this->cNodes[] = $node;
    }

    #region implements ParentNodeInterface

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
