<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\DocumentFragmentInterface;
use Manychois\Simdom\DocumentTypeInterface;
use Manychois\Simdom\NodeType;

/**
 * Internal implementation of DocumentFragmentInterface
 */
class DocFragmentNode extends AbstractParentNode implements DocumentFragmentInterface
{
    #region extends AbstractParentNode

    /**
     * @inheritDoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::DocumentFragment;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        return $this->cNodes->toHtml();
    }

    /**
     * @inheritDoc
     */
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): int
    {
        $index = parent::validatePreInsertion($nodes, $ref);
        foreach ($nodes as $node) {
            if ($node instanceof DocumentTypeInterface) {
                throw new InvalidArgumentException('DocumentType cannot be a child of a DocumentFragment.');
            }
        }

        return $index;
    }

    /**
     * @inheritDoc
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        $index = parent::validatePreReplace($old, $newNodes);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentTypeInterface) {
                throw new InvalidArgumentException('DocumentType cannot be a child of a DocumentFragment.');
            }
        }

        return $index;
    }

    #endregion
}
