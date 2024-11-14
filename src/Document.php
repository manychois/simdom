<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\AbstractParentNode;
use Manychois\Simdom\Parsing\DomParser;

/**
 * Represents a document node in the DOM tree.
 */
class Document extends AbstractParentNode
{
    public ?Doctype $doctype;

    /**
     * Constructs a new instance of this class.
     */
    public function __construct()
    {
        parent::__construct(false);

        $this->doctype = new Doctype('html');
    }

    /**
     * Gets the body element of this document, if any.
     *
     * @return Element|null The body element of this document, or null if there is none.
     */
    public function body(): ?Element
    {
        $docEle = $this->documentElement();
        if ($docEle !== null && $docEle->tagName === 'html') {
            foreach ($docEle->childNodeList->elements() as $child) {
                if ($child->tagName === 'body') {
                    return $child;
                }
            }
        }

        return null;
    }

    /**
     * Gets the document element of this document.
     *
     * @return Element|null The document element of this document, or null if there is none.
     */
    public function documentElement(): ?Element
    {
        foreach ($this->childNodeList as $node) {
            if ($node instanceof Element) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the head element of this document, if any.
     *
     * @return Element|null The head element of this document, or null if there is none.
     */
    public function head(): ?Element
    {
        $docEle = $this->documentElement();
        if ($docEle !== null && $docEle->tagName === 'html') {
            foreach ($docEle->childNodeList->elements() as $child) {
                if ($child->tagName === 'head') {
                    return $child;
                }
            }
        }

        return null;
    }

    #region extends AbstractParentNode

    /**
     * @inheritDoc
     */
    protected function validatePreInsertion(string|AbstractNode ...$futureChildren): void
    {
        $docEle = null;
        foreach ($futureChildren as $node) {
            if ($node instanceof self) {
                throw new \InvalidArgumentException('Document cannot be a child node.');
            }

            if (\is_string($node) || $node instanceof Text) {
                throw new \InvalidArgumentException('Text node cannot be a child node of a document.');
            }

            if (!($node instanceof Element)) {
                continue;
            }

            if ($docEle !== null) {
                throw new \InvalidArgumentException('Document can have only one child element.');
            }
            $docEle = $node;
        }
    }

    /**
     * @inheritDoc
     */
    public function clone(bool $deep = true): self
    {
        $clone = new self();
        if ($this->doctype === null) {
            $clone->doctype = null;
        } elseif ($deep) {
            $clone->doctype = clone $this->doctype;
        }
        if ($deep) {
            $this->cloneChildNodeList($clone);
        }

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function equals(?AbstractNode $node): bool
    {
        if (!($node instanceof self)) {
            return false;
        }

        if ($this->doctype === null) {
            if ($node->doctype !== null) {
                return false;
            }
        } else {
            if (
                $node->doctype === null ||
                $this->doctype->name !== $node->doctype->name ||
                $this->doctype->publicId !== $node->doctype->publicId ||
                $this->doctype->systemId !== $node->doctype->systemId
            ) {
                return false;
            }
        }

        return $this->isEqualChildNodeList($node);
    }

    /**
     * @inheritDoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::Document;
    }

    /**
     * @inheritDoc
     */
    public function setInnerHtml(string $html): void
    {
        $domParser = new DomParser();
        $parsed = $domParser->parseDocument($html);
        $this->doctype = $parsed->doctype;
        $this->clear();
        $this->append(...$parsed->childNodeList->toArray());
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $html = '';
        if ($this->doctype !== null) {
            $html .= $this->doctype->toHtml();
        }
        foreach ($this->childNodeList as $node) {
            $html .= $node->toHtml();
        }

        return $html;
    }

    #endregion extends AbstractParentNode
}
