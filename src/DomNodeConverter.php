<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use DOMComment;
use DOMDocument;
use DOMDocumentFragment;
use DOMDocumentType;
use DOMElement;
use DOMNode;
use DOMText;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentFragment;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\CommentNode;
use Manychois\Simdom\Internal\DocFragNode;
use Manychois\Simdom\Internal\DocNode;
use Manychois\Simdom\Internal\DoctypeNode;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Internal\TextNode;
use Manychois\Simdom\Node;
use Manychois\Simdom\Text;

/**
 * Provides methods to convert a DOM node to a Simdom node, and vice versa.
 */
class DomNodeConverter
{
    #region Convert from DOM to Simdom

    /**
     * Converts a `DOMNode` to a Simdom `Node`.
     * It may fail if the node is not supported e.g. `DOMAttr`, `DOMCdataSection`.
     * @param DOMNode $source The node to convert.
     * @param bool $includeAncestors Whether to convert the ancestors of the node.
     */
    public function convertToNode(DOMNode $source, bool $includeAncestors = false): Node
    {
        if ($includeAncestors) {
            return $this->convertFromRoot($this->getRootDomNode($source), $source);
        }
        if ($source instanceof DOMDocument || $source instanceof DOMDocumentFragment || $source instanceof DOMElement) {
            return $this->convertFromRoot($source, $source);
        }
        return $this->convertWithoutChild($source);
    }

    /**
     * Converts a `DOMComment` to a Simdom `Comment`.
     * @param DOMComment $source The comment node to convert.
     * @param bool $includeAncestors Whether to convert the ancestors of the node.
     */
    public function convertToComment(DOMComment $source, bool $includeAncestors = false): Comment
    {
        return $this->convertToNode($source, $includeAncestors);
    }

    /**
     * Converts a `DOMText` to a Simdom `Text`.
     * @param DOMText $source The text node to convert.
     * @param bool $includeAncestors Whether to convert the ancestors of the node.
     */
    public function convertToText(DOMText $source, bool $includeAncestors = false): Text
    {
        return $this->convertToNode($source, $includeAncestors);
    }

    /**
     * Converts a `DOMElement` to a Simdom `Element`.
     * @param DOMElement $source The element node to convert.
     * @param bool $includeAncestors Whether to convert the ancestors of the node.
     */
    public function convertToElement(DOMElement $source, bool $includeAncestors = false): Element
    {
        return $this->convertToNode($source, $includeAncestors);
    }

    /**
     * Converts a `DOMDocument` to a Simdom `Document`.
     * @param DOMDocument $source The document node to convert.
     */
    public function convertToDocument(DOMDocument $source): Document
    {
        return $this->convertToNode($source, false);
    }

    /**
     * Converts a `DOMDocumentFragment` to a Simdom `DocumentFragment`.
     * @param DOMDocumentFragment $source The document fragment node to convert.
     */
    public function convertToDocumentFragment(DOMDocumentFragment $source): DocumentFragment
    {
        return $this->convertToNode($source, false);
    }

    /**
     * Converts a `DOMDocumentType` to a Simdom `DocumentType`.
     * @param DOMDocumentType $source The document type node to convert.
     * @param bool $includeAncestors Whether to convert the ancestors of the node.
     */
    public function convertToDocumentType(DOMDocumentType $source, bool $includeAncestors = false): DocumentType
    {
        return $this->convertToNode($source, $includeAncestors);
    }

    protected function convertFromRoot(DOMNode $source, DOMNode $target): Node
    {
        $root = $this->convertWithoutChild($source);
        $toReturn = $root;
        $queue = [];
        foreach ($source->childNodes as $node) {
            $queue[] = [$node, $root];
        }
        while ($queue) {
            /** @var \Manychois\Simdom\Internal\BaseParentNode $parent */
            [$node, $parent] = array_shift($queue);
            $converted = $this->convertWithoutChild($node);
            if ($node === $target) {
                $toReturn = $converted;
            }
            $parent->nodeList->simAppend($converted);
            foreach ($node->childNodes as $child) {
                $queue[] = [$child, $converted];
            }
        }
        return $toReturn;
    }

    protected function convertWithoutChild(DOMNode $source): Node
    {
        switch ($source->nodeType) {
            case XML_DOCUMENT_NODE:
            case XML_HTML_DOCUMENT_NODE:
                return new DocNode();
            case XML_DOCUMENT_FRAG_NODE:
                return new DocFragNode();
            case XML_COMMENT_NODE:
                assert($source instanceof DOMComment);
                return new CommentNode($source->data);
            case XML_DOCUMENT_TYPE_NODE:
                assert($source instanceof DOMDocumentType);
                return new DoctypeNode($source->name, $source->publicId, $source->systemId);
            case XML_TEXT_NODE:
                assert($source instanceof DOMText);
                return new TextNode($source->data);
            case XML_ELEMENT_NODE:
                assert($source instanceof DOMElement);
                $element = new ElementNode($source->localName, $source->namespaceURI ?? DomNs::HTML);
                foreach ($source->attributes as $attr) {
                    /** @var \DOMAttr $attr */
                    if ($attr->prefix) {
                        $element->setAttributeNS(
                            $attr->namespaceURI,
                            $attr->prefix . ':' . $attr->localName,
                            $attr->value
                        );
                    } else {
                        $element->setAttribute($attr->localName, $attr->value);
                    }
                }
                return $element;
            default:
                throw new \InvalidArgumentException(
                    sprintf("Unsupported node type: %d, node name: %s", $source->nodeType, $source->nodeName)
                );
        }
    }

    protected function getRootDomNode(DOMNode $node): DOMNode
    {
        $root = $node;
        while ($root->parentNode) {
            $root = $root->parentNode;
        }
        return $root;
    }

    #endregion

    #region Convert from Simdom to DOM

    protected function importNodeWithoutChild(Node $source, DOMDocument $doc): DOMNode
    {
        if ($source instanceof Comment) {
            return $doc->createComment($source->data());
        }
        if ($source instanceof Text) {
            return $doc->createTextNode($source->data());
        }
        if ($source instanceof DocumentType) {
            return $doc->implementation->createDocumentType(
                $source->name(),
                $source->publicId(),
                $source->systemId()
            );
        }
        if ($source instanceof DocumentFragment) {
            return $doc->createDocumentFragment();
        }
        if ($source instanceof Element) {
            if ($source->namespaceURI() === DomNs::HTML) {
                $element = $doc->createElement($source->localName());
            } else {
                $element = $doc->createElementNS($source->namespaceURI(), $source->localName());
            }
            foreach ($source->attributes() as $attr) {
                $element->setAttributeNS($attr->namespaceURI(), $attr->name(), $attr->value());
            }
            return $element;
        }
        throw new \InvalidArgumentException(sprintf("Unsupported node class: %s", get_class($source)));
    }

    /**
     * Converts a Simdom `Node` to a `DOMNode` which later can be inserted into the given `DOMDocument`.
     * To convert a Simdom `Document` to a `DOMDocument`, use `convertToDomDocument()` instead.
     * @param Node $source The node to convert.
     * @param DOMDocument $doc The target document to insert the converted node into.
     */
    public function importNode(Node $source, DOMDocument $doc): DOMNode
    {
        $node = $this->importNodeWithoutChild($source, $doc);
        if ($source instanceof Element || $source instanceof DocumentFragment) {
            $queue = [];
            foreach ($source->childNodes() as $child) {
                $queue[] = [$child, $node];
            }
            while ($queue) {
                /** @var DomNode $parent */
                [$child, $parent] = array_shift($queue);
                $imported = $this->importNodeWithoutChild($child, $doc);
                $parent->appendChild($imported);
                if ($child instanceof Element) {
                    foreach ($child->childNodes() as $grandChild) {
                        $queue[] = [$grandChild, $imported];
                    }
                }
            }
        }
        return $node;
    }

    /**
     * Converts a Simdom `Text` to a `DOMText` which later can be inserted into the given `DOMDocument`.
     * @param Text $source The text node to convert.
     * @param DOMDocument $doc The target document to insert the converted text node into.
     */
    public function importText(Text $source, DOMDocument $doc): DOMText
    {
        return $this->importNodeWithoutChild($source, $doc);
    }

    /**
     * Converts a Simdom `Comment` to a `DOMComment` which later can be inserted into the given `DOMDocument`.
     * @param Comment $source The comment node to convert.
     * @param DOMDocument $doc The target document to insert the converted comment node into.
     */
    public function importComment(Comment $source, DOMDocument $doc): DOMComment
    {
        return $this->importNodeWithoutChild($source, $doc);
    }

    /**
     * Converts a Simdom `DocumentType` to a `DOMDocumentType` which later can be inserted into the given `DOMDocument`.
     * @param DocumentType $source The document type node to convert.
     * @param DOMDocument $doc The target document to insert the converted document type node into.
     */
    public function importDocumentType(DocumentType $source, DOMDocument $doc): DOMDocumentType
    {
        return $this->importNodeWithoutChild($source, $doc);
    }

    /**
     * Converts a Simdom `Element` to a `DOMElement` which later can be inserted into the given `DOMDocument`.
     * @param Element $source The element node to convert.
     * @param DOMDocument $doc The target document to insert the converted element node into.
     */
    public function importElement(Element $source, DOMDocument $doc): DOMElement
    {
        return $this->importNode($source, $doc);
    }

    /**
     * Converts a Simdom `DocumentFragment` to a `DOMDocumentFragment` which later can be inserted into the given
     * `DOMDocument`.
     * @param DocumentFragment $source The document fragment node to convert.
     * @param DOMDocument $doc The target document to insert the converted document fragment node into.
     */
    public function importDocumentFragment(DocumentFragment $source, DOMDocument $doc): DOMDocumentFragment
    {
        return $this->importNode($source, $doc);
    }

    /**
     * Converts a Simdom `Document` to a `DOMDocument`.
     */
    public function convertToDOMDocument(Document $source): DOMDocument
    {
        $doc = new DOMDocument();
        $queue = [];
        foreach ($source->childNodes() as $child) {
            $queue[] = [$child, $doc];
        }
        while ($queue) {
            /** @var DomNode $parent */
            [$child, $parent] = array_shift($queue);
            $imported = $this->importNodeWithoutChild($child, $doc);
            $parent->appendChild($imported);
            if ($child instanceof Element) {
                foreach ($child->childNodes() as $grandChild) {
                    $queue[] = [$grandChild, $imported];
                }
            }
        }
        return $doc;
    }

    #endregion
}
