<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Attr;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Element;
use Manychois\Simdom\PrettyPrintOption;

class DomPrinter
{
    /**
     * @var array<int, string>
     */
    private array $indentCache = [];

    public function print(Document $doc, PrettyPrintOption $option): string
    {
        $this->indentCache = [];
        $s = '';
        $queue = [];
        $specialTextModes = [];
        foreach ($doc->childNodes() as $node) {
            $queue[] = [0, $node];
        }
        while ($queue) {
            $queueItem = array_shift($queue);
            $depth = $queueItem[0];
            $node = $queueItem[1];
            $indent = $this->getIndent($depth, $option);
            $sEndsWithNewline = $s === '' || substr($s, -1) === "\n";

            if ($node instanceof Comment) {
                if (!$sEndsWithNewline) {
                    $s .= "\n";
                }
                $s .= $indent . '<!--' . $node->data() . "-->\n";
            } elseif ($node instanceof DocumentType) {
                $s .= $this->getDocumentTypeString($node) . "\n";
            } elseif ($node instanceof TextNode) {
                $text = $node->serialize();
                if ($specialTextModes) {
                    $s .= $text;
                } else {
                    $minimized = BaseNode::escapeString(preg_replace('/\s+/', ' ', $text));
                    if ($sEndsWithNewline) {
                        if (!ctype_space($minimized)) {
                            $s .= $indent . ltrim($minimized);
                        }
                    } else {
                        $s .= $minimized;
                    }
                }
            } elseif ($node instanceof Element) {
                if ($this->willPutNewLineBeforeStartTag($node)) {
                    if (!$sEndsWithNewline) {
                        $s .= "\n";
                    }
                    if (!$specialTextModes) {
                        $s .= $indent;
                    }
                } else {
                    if ($sEndsWithNewline && !$specialTextModes) {
                        $s .= $indent;
                    }
                }
                $name = $node->localName();
                $s .= '<' . $name;
                foreach ($node->attributes() as $attr) {
                    $s .= ' ' . $this->getAttrString($attr, $option);
                }
                if (ElementNode::isVoid($name)) {
                    $s .= ' />';
                    if ($this->willPutNewLineAfterEndTag($node)) {
                        $s .= "\n";
                    }
                } else {
                    $s .= '>';
                    if ($node->hasChildNodes()) {
                        if ($this->willPutNewLineAfterStartTag($node)) {
                            $s .= "\n";
                        }
                        $toPrepend = [];
                        if ($node->namespaceURI() === DomNs::Html && $name === 'html') {
                            $childDepth = $depth;
                        } else {
                            $childDepth = $depth + 1;
                        }
                        foreach ($node->childNodes() as $child) {
                            $toPrepend[] = [$childDepth, $child];
                        }
                        $toPrepend[] = [$depth, null, $node];
                        array_unshift($queue, ...$toPrepend);
                        if (TextOnlyElementNode::match($name) || $name === 'pre') {
                            $specialTextModes[] = $name;
                        }
                    } else {
                        $s .= '</' . $name . '>';
                        if ($this->willPutNewLineAfterEndTag($node)) {
                            $s .= "\n";
                        }
                    }
                }
            } else { // end tag case
                /** @var ElementNode $tag */
                $tag = $queueItem[2];
                $name = $tag->localName();
                if ($tag->namespaceURI() === DomNs::Html && (TextOnlyElementNode::match($name) || $name === 'pre')) {
                    array_pop($specialTextModes);
                }
                if ($this->willPutNewLineBeforeEndTag($tag)) {
                    if (!$sEndsWithNewline) {
                        $s .= "\n";
                    }
                    $s .= $indent;
                }
                $s .= '</' . $tag->localName() . '>';
                if ($this->willPutNewLineAfterEndTag($tag)) {
                    $s .= "\n";
                }
            }
        }
        return $s;
    }

    public function getIndent(int $depth, PrettyPrintOption $option): string
    {
        if (isset($this->indentCache[$depth])) {
            return $this->indentCache[$depth];
        }
        if ($depth <= 0) {
            $this->indentCache = ['', $option->indent];
            return '';
        }
        $s = $this->getIndent($depth - 1, $option) . $option->indent;
        $this->indentCache[$depth] = $s;
        return $s;
    }

    public function getAttrString(Attr $attr, PrettyPrintOption $option): string
    {
        $n = $attr->name();
        $v = $attr->value();
        $s = $n;
        if ($v === '' && AttrNode::isBoolean($n)) {
            // skip value output
        } else {
            $s .= '=';
            $quote = '"';
            if ($option->escAttrValue) {
                $s .= $quote . BaseNode::escapeString($v, true) . $quote;
            } else {
                if (strpos($v, $quote) === false) {
                    $s .= $quote . $v . $quote;
                } else {
                    $quote = "'";
                    if (strpos($v, $quote) === false) {
                        $s .= $quote . $v . $quote;
                    } else {
                        $s .= '"' . str_replace('"', '&quot;', $v) . '"';
                    }
                }
            }
        }
        return $s;
    }

    public function getDocumentTypeString(DocumentType $docType): string
    {
        $s = '<!DOCTYPE';
        if ($docType->name()) {
            $s .= ' ' . $docType->name();
        }
        if ($docType->publicId() !== '') {
            $s .= ' PUBLIC "' . $docType->publicId() . '"';
            if ($docType->systemId() !== '') {
                $s .= ' "' . $docType->systemId() . '"';
            }
        } elseif ($docType->systemId() !== '') {
            $s .= ' SYSTEM "' . $docType->systemId() . '"';
        }
        $s .= '>';
        return $s;
    }

    protected function isInlineElement(Element $element): bool
    {
        return $element->namespaceURI() === DomNs::Html && in_array($element->localName(), [
            'a',
            'abbr',
            'acronym',
            'audio',
            'b',
            'bdi',
            'bdo',
            'big',
            'br',
            'button',
            'canvas',
            'cite',
            'code',
            'data',
            'datalist',
            'del',
            'dfn',
            'em',
            'embed',
            'i',
            'iframe',
            'img',
            'input',
            'ins',
            'kbd',
            'label',
            'map',
            'mark',
            'meter',
            'noscript',
            'object',
            'output',
            'picture',
            'progress',
            'q',
            'ruby',
            's',
            'samp',
            'script',
            'select',
            'slot',
            'small',
            'span',
            'strong',
            'sub',
            'sup',
            'svg',
            'template',
            'textarea',
            'time',
            'u',
            'tt',
            'var',
            'video',
            'wbr',
        ], true);
    }

    protected function willPutNewLineBeforeStartTag(Element $element): bool
    {
        if ($element->namespaceURI() === DomNs::Html) {
            return !$this->isInlineElement($element);
        }
        return true;
    }

    protected function willPutNewLineAfterStartTag(Element $element): bool
    {
        if ($element->namespaceURI() === DomNs::Html) {
            $name = $element->localName();
            return $name !== 'pre' && !TextOnlyElementNode::match($name) && !$this->isInlineElement($element);
        }
        return true;
    }

    protected function willPutNewLineBeforeEndTag(Element $element): bool
    {
        return $this->willPutNewLineAfterStartTag($element);
    }

    protected function willPutNewLineAfterEndTag(Element $element): bool
    {
        return $this->willPutNewLineBeforeStartTag($element);
    }
}
