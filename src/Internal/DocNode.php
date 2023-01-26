<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeType;
use Manychois\Simdom\Text;

class DocNode extends BaseParentNode implements Document
{
    /**
     * Document constraints:
     * 1. At most 1 DocumentType
     * 2. At most 1 Element
     * 3. No Text
     * 4. DocumentType is before Element
     */

    public function __construct()
    {
        parent::__construct();
    }

    #region implements Document properties

    public function body(): ?Element
    {
        $html = $this->documentElement();
        if ($html?->tagName() === 'HTML') {
            foreach ($html->children() as $ele) {
                if ($ele->tagName() === 'BODY') {
                    return $ele;
                }
            }
        }
        return null;
    }

    public function doctype(): ?DocumentType
    {
        $i = $this->nodeList->findIndex(fn (Node $n) => $n instanceof DocumentType);
        return $this->nodeList->item($i);
    }

    public function documentElement(): ?Element
    {
        return $this->children()->item(0);
    }

    public function head(): ?Element
    {
        $html = $this->documentElement();
        if ($html?->tagName() === 'HTML') {
            foreach ($html->children() as $ele) {
                if ($ele->tagName() === 'HEAD') {
                    return $ele;
                }
            }
        }
        return null;
    }

    #endregion

    #region overrides BaseParentNode

    /**
     * @param array<Node> $nodes
     */
    protected function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        parent::validatePreInsertion($child, $nodes);

        $getEx = fn (Node $node, string $msg) => new PreInsertionException($this, $node, $child, $msg);

        $nodeList = $this->nodeList;
        $insertAt = $child ? $nodeList->indexOf($child) : $nodeList->length();
        $doctypeIdx = $child instanceof DocumentType ? $insertAt :
            $nodeList->findIndex(fn ($n) => $n instanceof DocumentType);
        $eIdx = $child instanceof Element ? $insertAt : $nodeList->findIndex(fn ($n) => $n instanceof Element);

        $newEIdx = -1;
        $newDocTypeIdx = -1;
        foreach ($nodes as $i => $node) {
            if ($node instanceof Text) {
                throw $getEx($node, 'Text cannot be a child of a Document.');
            } elseif ($node instanceof Element) {
                if ($newEIdx === -1) {
                    if ($eIdx !== -1) {
                        throw $getEx($node, 'Document can have only 1 root Element.');
                    }
                    if ($doctypeIdx !== -1 && $insertAt <= $doctypeIdx) {
                        throw $getEx($node, 'DocumentType must be before Element in a Document.');
                    }
                    $newEIdx = $i;
                } else {
                    throw $getEx($node, 'Document can have only 1 root Element.');
                }
            } elseif ($node instanceof DocumentType) {
                if ($doctypeIdx !== -1) {
                    throw $getEx($node, 'Document can have only 1 DocumentType.');
                }
                if ($eIdx !== -1 && $insertAt > $eIdx) {
                    throw $getEx($node, 'DocumentType must be before Element in a Document.');
                }
                if ($newDocTypeIdx === -1) {
                    $newDocTypeIdx = $i;
                } else {
                    throw $getEx($node, 'Document can have only 1 DocumentType.');
                }
            }
        }
        if ($newDocTypeIdx >= 0 && $newEIdx >= 0 && $newDocTypeIdx > $newEIdx) {
            throw $getEx($nodes[$newDocTypeIdx], 'DocumentType must be before Element in a Document.');
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    protected function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        parent::validatePreReplace($old, $newNodes);

        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, $old, $msg);

        $nodeList = $this->nodeList;
        $replaceAt = $nodeList->indexOf($old);
        $doctypeIdx = $old instanceof DocumentType ? $replaceAt :
            $nodeList->findIndex(fn ($n) => $n instanceof DocumentType);
        $eIdx = $old instanceof Element ? $replaceAt : $nodeList->findIndex(fn ($n) => $n instanceof Element);

        $newEIdx = -1;
        $newDocTypeIdx = -1;
        foreach ($newNodes as $i => $new) {
            if ($new instanceof Text) {
                throw $getEx($new, 'Text cannot be a child of a Document.');
            } elseif ($new instanceof Element) {
                if ($newEIdx === -1) {
                    if ($eIdx !== -1 && $replaceAt !== $eIdx) {
                        throw $getEx($new, 'Document can have only 1 root Element.');
                    }
                    if ($doctypeIdx !== -1 && $replaceAt <= $doctypeIdx) {
                        throw $getEx($new, 'DocumentType must be before Element in a Document.');
                    }
                    $newEIdx = $i;
                } else {
                    throw $getEx($new, 'Document can have only 1 root Element.');
                }
            } elseif ($new instanceof DocumentType) {
                if ($doctypeIdx !== -1 && $doctypeIdx !== $replaceAt) {
                    throw $getEx($new, 'Document can have only 1 DocumentType.');
                }
                if ($eIdx !== -1 && $replaceAt > $eIdx) {
                    throw $getEx($new, 'DocumentType must be before Element in a Document.');
                }
                if ($newDocTypeIdx === -1) {
                    if ($newEIdx !== -1) {
                        throw $getEx($new, 'DocumentType must be before Element in a Document.');
                    }
                    $newDocTypeIdx = $i;
                } else {
                    throw $getEx($new, 'Document can have only 1 DocumentType.');
                }
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    protected function validatePreReplaceAll(array $newNodes): void
    {
        parent::validatePreReplaceAll($newNodes);

        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, null, $msg);

        $newEIdx = -1;
        $newDocTypeIdx = -1;
        foreach ($newNodes as $i => $new) {
            if ($new instanceof Text) {
                throw $getEx($new, 'Text cannot be a child of a Document.');
            } elseif ($new instanceof Element) {
                if ($newEIdx === -1) {
                    $newEIdx = $i;
                } else {
                    throw $getEx($new, 'Document can have only 1 root Element.');
                }
            } elseif ($new instanceof DocumentType) {
                if ($newDocTypeIdx === -1) {
                    if ($newEIdx !== -1) {
                        throw $getEx($new, 'DocumentType must be before Element in a Document.');
                    }
                    $newDocTypeIdx = $i;
                } else {
                    throw $getEx($new, 'Document can have only 1 DocumentType.');
                }
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
        return NodeType::Document;
    }

    #endregion
}
