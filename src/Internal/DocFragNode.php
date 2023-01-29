<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\DocumentFragment;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Internal\BaseNode;
use Manychois\Simdom\Internal\BaseParentNode;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeType;

class DocFragNode extends BaseParentNode implements DocumentFragment
{
    #region overrides BaseParentNode

    /**
     * @param array<Node> $nodes
     */
    public function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        parent::validatePreInsertion($child, $nodes);
        $getEx = fn (Node $node, string $msg) => new PreInsertionException($this, $node, $child, $msg);
        foreach ($nodes as $node) {
            if ($node instanceof DocumentType) {
                throw $getEx($node, 'DocumentType cannot be a child of a DocumentFragment.');
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        parent::validatePreReplace($old, $newNodes);
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, $old, $msg);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentType) {
                throw $getEx('DocumentType cannot be a child of an Element.');
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplaceAll(array $newNodes): void
    {
        parent::validatePreReplaceAll($newNodes);
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, null, $msg);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentType) {
                throw $getEx($new, 'DocumentType cannot be a child of an Element.');
            }
        }
    }

    #endregion

    #region overrides BaseNode

    public function cloneNode(bool $deep = false): static
    {
        $clone = new static();
        if ($deep) {
            foreach ($this->nodeList as $child) {
                $clone->nodeList->simAppend($child->cloneNode(true));
            }
        }
        return $clone;
    }

    public function nodeType(): NodeType
    {
        return NodeType::DocumentFragment;
    }

    public function textContent(): ?string
    {
        return null;
    }

    public function textContentSet(string $data): void
    {
    }

    #endregion
}
