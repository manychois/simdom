<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\DocumentFragment;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Internal\BaseNode;
use Manychois\Simdom\Internal\BaseParentNode;
use Manychois\Simdom\Node;

class DocFragNode extends BaseParentNode implements DocumentFragment
{
    #region overrides BaseParentNode

    /**
     * @param array<Node> $nodes
     */
    public function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        parent::validatePreInsertion($child, $nodes);
        foreach ($nodes as $node) {
            if ($node instanceof DocumentType) {
                throw new PreInsertionException(
                    $this,
                    $node,
                    $child,
                    'DocumentType cannot be a child of a DocumentFragment.'
                );
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        parent::validatePreReplace($old, $newNodes);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentType) {
                throw new PreReplaceException(
                    $this,
                    $new,
                    $old,
                    'DocumentType cannot be a child of a DocumentFragment.'
                );
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplaceAll(array $newNodes): void
    {
        parent::validatePreReplaceAll($newNodes);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentType) {
                throw new PreReplaceException(
                    $this,
                    $new,
                    null,
                    'DocumentType cannot be a child of a DocumentFragment.'
                );
            }
        }
    }

    #endregion

    #region overrides BaseNode

    public function cloneNode(bool $deep = false): self
    {
        $clone = new static();
        if ($deep) {
            foreach ($this->nodeList as $child) {
                $clone->nodeList->simAppend($child->cloneNode(true));
            }
        }
        return $clone;
    }

    public function nodeType(): int
    {
        return Node::DOCUMENT_FRAGMENT_NODE;
    }

    #endregion
}
