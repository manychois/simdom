<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\Simdom\Node;
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

    #region implements Document properties

    public function body(): ?Element
    {
        $html = $this->documentElement();
        if ($html && $html->tagName() === 'HTML') {
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
        $i = $this->nodeList->findIndex(function (Node $n) {
            return $n instanceof DocumentType;
        });
        return $this->nodeList->item($i);
    }

    public function documentElement(): ?Element
    {
        return $this->children()->item(0);
    }

    public function head(): ?Element
    {
        $html = $this->documentElement();
        if ($html && $html->tagName() === 'HTML') {
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
        return Node::DOCUMENT_NODE;
    }

    public function textContent(): ?string
    {
        return null;
    }

    public function textContentSet(string $data): void
    {
        // Do nothing.
    }

    /**
     * @param array<Node> $nodes
     */
    public function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        parent::validatePreInsertion($child, $nodes);

        $nodeList = $this->nodeList;
        $insertAt = $child ? $nodeList->indexOf($child) : $nodeList->length();
        $doctypeIdx = $child instanceof DocumentType ? $insertAt :
            $nodeList->findIndex(function ($n) {
                return $n instanceof DocumentType;
            });
        $eIdx = $child instanceof Element ? $insertAt : $nodeList->findIndex(function ($n) {
            return $n instanceof Element;
        });

        $newEIdx = -1;
        $newDocTypeIdx = -1;
        foreach ($nodes as $i => $node) {
            if ($node instanceof Text) {
                throw new PreInsertionException($this, $node, $child, 'Text cannot be a child of a Document.');
            } elseif ($node instanceof Element) {
                if ($newEIdx === -1) {
                    if ($eIdx !== -1) {
                        throw new PreInsertionException($this, $node, $child, 'Document can have only 1 root Element.');
                    }
                    if ($doctypeIdx !== -1 && $insertAt <= $doctypeIdx) {
                        throw new PreInsertionException(
                            $this,
                            $node,
                            $child,
                            'DocumentType must be before Element in a Document.'
                        );
                    }
                    $newEIdx = $i;
                } else {
                    throw new PreInsertionException($this, $node, $child, 'Document can have only 1 root Element.');
                }
            } elseif ($node instanceof DocumentType) {
                if ($doctypeIdx !== -1) {
                    throw new PreInsertionException($this, $node, $child, 'Document can have only 1 DocumentType.');
                }
                if ($eIdx !== -1 && $insertAt > $eIdx) {
                    throw new PreInsertionException(
                        $this,
                        $node,
                        $child,
                        'DocumentType must be before Element in a Document.'
                    );
                }
                if ($newDocTypeIdx === -1) {
                    $newDocTypeIdx = $i;
                } else {
                    throw new PreInsertionException($this, $node, $child, 'Document can have only 1 DocumentType.');
                }
            }
        }
        if ($newDocTypeIdx >= 0 && $newEIdx >= 0 && $newDocTypeIdx > $newEIdx) {
            throw new PreInsertionException(
                $this,
                $nodes[$newDocTypeIdx],
                $child,
                'DocumentType must be before Element in a Document.'
            );
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        parent::validatePreReplace($old, $newNodes);

        $nodeList = $this->nodeList;
        $replaceAt = $nodeList->indexOf($old);
        $doctypeIdx = $old instanceof DocumentType ? $replaceAt :
            $nodeList->findIndex(function ($n) {
                return $n instanceof DocumentType;
            });
        $eIdx = $old instanceof Element ? $replaceAt : $nodeList->findIndex(function ($n) {
            return $n instanceof Element;
        });

        $newEIdx = -1;
        foreach ($newNodes as $i => $new) {
            if ($new instanceof Text) {
                throw new PreReplaceException($this, $new, $old, 'Text cannot be a child of a Document.');
            } elseif ($new instanceof Element) {
                if ($newEIdx === -1) {
                    if ($eIdx !== -1 && $replaceAt !== $eIdx) {
                        throw new PreReplaceException($this, $new, $old, 'Document can have only 1 root Element.');
                    }
                    if ($doctypeIdx !== -1 && $replaceAt < $doctypeIdx) {
                        throw new PreReplaceException(
                            $this,
                            $new,
                            $old,
                            'DocumentType must be before Element in a Document.'
                        );
                    }
                    $newEIdx = $i;
                } else {
                    throw new PreReplaceException($this, $new, $old, 'Document can have only 1 root Element.');
                }
            } elseif ($new instanceof DocumentType) {
                if ($doctypeIdx !== -1 && $doctypeIdx !== $replaceAt) {
                    throw new PreReplaceException($this, $new, $old, 'Document can have only 1 DocumentType.');
                }
                if ($eIdx !== -1 && $replaceAt > $eIdx) {
                    throw new PreReplaceException(
                        $this,
                        $new,
                        $old,
                        'DocumentType must be before Element in a Document.'
                    );
                }
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplaceAll(array $newNodes): void
    {
        parent::validatePreReplaceAll($newNodes);

        $newEIdx = -1;
        $newDocTypeIdx = -1;
        foreach ($newNodes as $i => $new) {
            if ($new instanceof Text) {
                throw new PreReplaceException($this, $new, null, 'Text cannot be a child of a Document.');
            } elseif ($new instanceof Element) {
                if ($newEIdx === -1) {
                    $newEIdx = $i;
                } else {
                    throw new PreReplaceException($this, $new, null, 'Document can have only 1 root Element.');
                }
            } elseif ($new instanceof DocumentType) {
                if ($newDocTypeIdx === -1) {
                    if ($newEIdx !== -1) {
                        throw new PreReplaceException(
                            $this,
                            $new,
                            null,
                            'DocumentType must be before Element in a Document.'
                        );
                    }
                    $newDocTypeIdx = $i;
                } else {
                    throw new PreReplaceException($this, $new, null, 'Document can have only 1 DocumentType.');
                }
            }
        }
    }

    #endregion
}
