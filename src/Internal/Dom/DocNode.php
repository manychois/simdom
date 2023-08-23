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
        $doctypeIdx = $this->findIndex(fn ($n) => $n instanceof DocumentTypeInterface);
        $eIdx = $this->findIndex(fn ($n) => $n instanceof ElementInterface);

        $newEIdx = -1;
        $newDocTypeIdx = -1;
        foreach ($nodes as $i => $node) {
            if ($node instanceof TextInterface) {
                throw new InvalidArgumentException('Text cannot be a child of a Document.');
            }

            if ($node instanceof ElementInterface) {
                if ($newEIdx === -1) {
                    if ($eIdx !== -1) {
                        throw new InvalidArgumentException('Document can have only 1 root Element.');
                    }

                    if ($doctypeIdx !== -1 && $insertAt <= $doctypeIdx) {
                        throw new InvalidArgumentException('DocumentType must be before Element in a Document.');
                    }
                    $newEIdx = $i;
                } else {
                    throw new InvalidArgumentException('Document can have only 1 root Element.');
                }
            }

            if ($node instanceof DocumentTypeInterface) {
                if ($doctypeIdx !== -1) {
                    throw new InvalidArgumentException('Document can have only 1 DocumentType.');
                }

                if ($eIdx !== -1 && $insertAt > $eIdx) {
                    throw new InvalidArgumentException('DocumentType must be before Element in a Document.');
                }

                if ($newDocTypeIdx === -1) {
                    $newDocTypeIdx = $i;
                } else {
                    throw new InvalidArgumentException('Document can have only 1 DocumentType.');
                }
            }
        }

        if ($newDocTypeIdx >= 0 && $newEIdx >= 0 && $newDocTypeIdx > $newEIdx) {
            throw new InvalidArgumentException('DocumentType must be before Element in a Document.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        $replaceAt = parent::validatePreReplace($old, $newNodes);
        $doctypeIdx = $this->findIndex(fn ($n) => $n instanceof DocumentTypeInterface);
        $eIdx = $this->findIndex(fn ($n) => $n instanceof ElementInterface);

        $newEIdx = -1;
        foreach ($newNodes as $i => $new) {
            if ($new instanceof TextInterface) {
                throw new InvalidArgumentException('Text cannot be a child of a Document.');
            }

            if ($new instanceof ElementInterface) {
                if ($newEIdx === -1) {
                    if ($eIdx !== -1 && $replaceAt !== $eIdx) {
                        throw new InvalidArgumentException('Document can have only 1 root Element.');
                    }

                    if ($doctypeIdx !== -1 && $replaceAt < $doctypeIdx) {
                        throw new InvalidArgumentException('DocumentType must be before Element in a Document.');
                    }

                    $newEIdx = $i;
                } else {
                    throw new InvalidArgumentException('Document can have only 1 root Element.');
                }
            } elseif ($new instanceof DocumentTypeInterface) {
                if ($doctypeIdx !== -1 && $doctypeIdx !== $replaceAt) {
                    throw new InvalidArgumentException('Document can have only 1 DocumentType.');
                }

                if ($eIdx !== -1 && $replaceAt > $eIdx) {
                    throw new InvalidArgumentException('DocumentType must be before Element in a Document.');
                }
            }
        }

        return $replaceAt;
    }

    #endregion
}
