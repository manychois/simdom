<?php

declare(strict_types=1);

namespace Manychois\Simdom\Converters;

use Manychois\Simdom\Comment;
use Manychois\Simdom\Doctype;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\ElementKind;
use Manychois\Simdom\Text;

/**
 * Converts DOM nodes to nodes in this library.
 */
class DomConverter
{
    /**
     * Converts a DOM comment node to a comment node in this library.
     * Its parent node will be ignored.
     *
     * @param \DOMComment $domComment The DOM comment node to convert.
     *
     * @return Comment The converted comment node.
     */
    public function toComment(\DOMComment $domComment): Comment
    {
        return new Comment($domComment->data);
    }

    /**
     * Converts a DOM document to a document in this library.
     *
     * @param \DOMDocument $domDoc The DOM document to convert.
     *
     * @return Document The converted document.
     */
    public function toDocument(\DOMDocument $domDoc): Document
    {
        $doc = new Document();
        foreach ($domDoc->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $node = $this->toElement($child);
            } elseif ($child instanceof \DOMComment) {
                $node = $this->toComment($child);
            } elseif ($child instanceof \DOMDocumentType) {
                $doctype = new Doctype($child->name, $child->publicId, $child->systemId);
                $doc->doctype = $doctype;

                continue;
            } else {
                continue;
            }
            $doc->childNodeList->🚫append($doc, $node);
        }

        // Ensure that the document has valid <html>, <head>, and <body> elements.
        $docEle = $doc->documentElement();
        $head = null;
        $body = null;
        if ($docEle === null) {
            $docEle = new Element('html');
            $doc->childNodeList->🚫append($doc, $docEle);
        } elseif ($docEle->tagName !== 'html') {
            $doc->childNodeList->🚫remove($docEle);
            $htmlEle = new Element('html');
            if ($docEle->tagName === 'head') {
                $head = $docEle;
                $htmlEle->childNodeList->🚫append($htmlEle, $head);
            } elseif ($docEle->tagName === 'body') {
                $body = $docEle;
                $htmlEle->childNodeList->🚫append($htmlEle, $body);
            } else {
                $body = new Element('body');
                $htmlEle->childNodeList->🚫append($htmlEle, $body);
                $body->childNodeList->🚫append($body, $docEle);
            }
        } else {
            foreach ($docEle->childNodeList->elements() as $child) {
                if ($head === null && $child->tagName === 'head') {
                    $head = $child;
                } elseif ($body === null && $child->tagName === 'body') {
                    $body = $child;
                }
            }
        }

        if ($head === null) {
            $head = new Element('head');
            if ($body === null) {
                $docEle->childNodeList->🚫append($docEle, $head);
            } else {
                $docEle->childNodeList->🚫insertAt($docEle, $body->index(), $head);
            }
        }

        if ($body === null) {
            $body = new Element('body');
            $docEle->childNodeList->🚫append($docEle, $body);
        }

        $extraHeads = [];
        $beforeBody = [];
        $afterBody = [];
        $bodyEncountered = false;
        foreach ($docEle->childNodeList->elements() as $child) {
            if ($child === $head) {
                continue;
            }
            if ($child === $body) {
                $bodyEncountered = true;

                continue;
            }
            if ($child->tagName === 'head') {
                $extraHeads[] = $child;

                continue;
            }
            if ($bodyEncountered) {
                $afterBody[] = $child;
            } else {
                $beforeBody[] = $child;
            }
        }
        foreach ($extraHeads as $extraHead) {
            $docEle->childNodeList->🚫remove($extraHead);
            $this->mergeElement($head, $extraHead);
        }
        if (\count($beforeBody) > 0) {
            foreach ($beforeBody as $child) {
                $docEle->childNodeList->🚫remove($child);
            }
            $body->childNodeList->🚫insertAt($body, 0, ...$beforeBody);
        }
        foreach ($afterBody as $child) {
            $docEle->childNodeList->🚫remove($child);
            if ($child->tagName === 'body') {
                $this->mergeElement($body, $child);
            } else {
                $body->childNodeList->🚫append($body, $child);
            }
        }

        return $doc;
    }

    /**
     * Converts a DOM element to an element in this library.
     * Its parent node will be ignored.
     *
     * @param \DOMElement $domEle The DOM element to convert.
     *
     * @return Element The converted element node.
     */
    public function toElement(\DOMElement $domEle): Element
    {
        $name = \strtolower($domEle->tagName);
        $ele = new Element($name);
        foreach ($domEle->attributes as $attr) {
            \assert($attr instanceof \DOMAttr);
            $ele->setAttr($attr->name, $attr->value);
        }

        $kind = $ele->🚫getKind();
        if ($kind === ElementKind::Void) {
            return $ele;
        }

        if ($kind === ElementKind::RawText || $kind === ElementKind::EscapableRawText) {
            $data = '';
            foreach ($domEle->childNodes as $child) {
                if ($child instanceof \DOMText) {
                    $node = $this->toText($child);
                    $data .= $node->data;
                } elseif ($child instanceof \DOMElement) {
                    $node = $this->toElement($child);
                    $data .= $node->toHtml();
                } elseif ($child instanceof \DOMComment) {
                    $node = $this->toComment($child);
                    $data .= $node->data;
                } else {
                    continue;
                }
            }
            $ele->childNodeList->🚫append($ele, $data);
        } else {
            foreach ($domEle->childNodes as $child) {
                if ($child instanceof \DOMText) {
                    $node = $this->toText($child);
                } elseif ($child instanceof \DOMElement) {
                    $node = $this->toElement($child);
                } elseif ($child instanceof \DOMComment) {
                    $node = $this->toComment($child);
                } else {
                    continue;
                }
                $ele->childNodeList->🚫append($ele, $node);
            }
        }

        return $ele;
    }

    /**
     * Converts a DOM text node to a text node in this library.
     * Its parent node will be ignored.
     *
     * @param \DOMText $domText The DOM text node to convert.
     *
     * @return Text The converted text node.
     */
    public function toText(\DOMText $domText): Text
    {
        return new Text($domText->data);
    }

    /**
     * Merges the attributes and child nodes of a duplicate element to a primary element.
     *
     * @param Element $primary   The primary element.
     * @param Element $duplicate The duplicate element.
     */
    private function mergeElement(Element $primary, Element $duplicate): void
    {
        foreach ($duplicate->attributes() as $attrName => $attrValue) {
            if ($primary->hasAttr($attrName)) {
                continue;
            }
            $primary->setAttr($attrName, $attrValue);
        }
        $duplicateChildNodes = $duplicate->childNodeList->toArray();
        $duplicate->childNodeList->🚫clear();
        foreach ($duplicateChildNodes as $child) {
            $primary->childNodeList->🚫append($primary, $child);
        }
    }
}
