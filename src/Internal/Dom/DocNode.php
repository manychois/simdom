<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\DocumentInterface;
use Manychois\Simdom\DocumentTypeInterface;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\NodeType;
use Manychois\Simdom\TextInterface;

/**
 * Internal implementation of DocumentInterface
 */
class DocNode extends AbstractParentNode implements DocumentInterface
{
    #region extends AbstractParentNode

    /**
     * @inheritdoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::Document;
    }

    /**
     * @inheritdoc
     */
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): void
    {
        parent::validatePreInsertion($nodes, $ref);

        $insertAt = $ref?->index() ?? count($this->cNodes);
        $temp = $this->cNodes;
        array_splice($temp, $insertAt, 0, $nodes);

        self::validateDocChildNodesOrder($temp);
    }

    /**
     * @inheritdoc
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        $replaceAt = parent::validatePreReplace($old, $newNodes);

        $temp = $this->cNodes;
        array_splice($temp, $replaceAt, 1, $newNodes);

        self::validateDocChildNodesOrder($temp);

        return $replaceAt;
    }

    #endregion

    /**
     * Validates the order of child nodes of a Document.
     * `InvalidArgumentException` is thrown if the validation fails.
     *
     * @param array<AbstractNode> $nodes The list of child nodes of a Document to validate.
     */
    private static function validateDocChildNodesOrder(array $nodes): void
    {
        $doctypeIdx = -1;
        $eIdx = -1;

        foreach ($nodes as $i => $node) {
            if ($node instanceof TextInterface) {
                throw new InvalidArgumentException('Text cannot be a child of a Document.');
            }

            if ($node instanceof ElementInterface) {
                if ($eIdx === -1) {
                    $eIdx = $i;
                } else {
                    throw new InvalidArgumentException('Document can have only 1 root Element.');
                }
            }

            if ($node instanceof DocumentTypeInterface) {
                if ($doctypeIdx === -1) {
                    $doctypeIdx = $i;
                    if ($eIdx !== -1 && $doctypeIdx > $eIdx) {
                        throw new InvalidArgumentException('DocumentType must be before Element in a Document.');
                    }
                } else {
                    throw new InvalidArgumentException('Document can have only 1 DocumentType.');
                }
            }
        }
    }
}
