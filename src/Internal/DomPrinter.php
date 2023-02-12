<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Attr;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentFragment;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Element;
use Manychois\Simdom\Node;
use Manychois\Simdom\PrettyPrintOption;
use Manychois\Simdom\Text;

class DomPrinter
{
    /**
     * @var array<int, string>
     */
    private array $indentCache;
    private PrettyPrintOption $option;

    public function __construct(PrettyPrintOption $option)
    {
        $this->indentCache = [];
        $this->option = $option;
    }

    public function print(Node $node): string
    {
        if ($node instanceof Comment) {
            return $this->printComment($node);
        }
        if ($node instanceof DocumentFragment) {
            return $this->printDocumentFragment($node);
        }
        if ($node instanceof DocumentType) {
            return $this->printDocumentType($node);
        }
        if ($node instanceof Element) {
            return $this->printElement($node);
        }
        if ($node instanceof Text) {
            return $this->printText($node);
        }
        return $this->printDocument($node);
    }

    protected function printComment(Comment $comment): string
    {
        assert($comment instanceof CommentNode);
        return $comment->serialize();
    }

    protected function printDocumentFragment(DocumentFragment $fragment): string
    {
        $queue = [];
        foreach ($fragment->childNodes() as $childNode) {
            $queue[] = [0, $childNode];
        }
        return $this->printNodeQueue($queue);
    }

    protected function printDocument(Document $doc): string
    {
        $queue = [];
        foreach ($doc->childNodes() as $node) {
            $queue[] = [0, $node];
        }
        return $this->printNodeQueue($queue);
    }

    protected function printDocumentType(DocumentType $docType): string
    {
        assert($docType instanceof DoctypeNode);
        return $docType->serialize();
    }

    protected function printElement(Element $element): string
    {
        $queue = [[0, $element]];
        return $this->printNodeQueue($queue);
    }

    protected function printText(Text $text): string
    {
        assert($text instanceof TextNode);
        return $text->serialize();
    }

    /**
     * @param array<array<null|int|BaseNode>> $queue
     */
    protected function printNodeQueue(array $queue): string
    {
        $s = '';
        $specialTextModes = [];
        $appendNewLine = false;
        while ($queue) {
            $queueItem = array_shift($queue);
            $depth = $queueItem[0];
            $node = $queueItem[1];
            $indent = $this->getIndent($depth);
            if ($appendNewLine) {
                $s .= "\n";
                $appendNewLine = false;
            }
            $sEndsWithNewline = $s === '' || substr($s, -1) === "\n";

            if ($node instanceof Comment) {
                if (!$sEndsWithNewline) {
                    $s .= "\n";
                }
                $s .= $indent . $this->printComment($node);
                $appendNewLine = true;
            } elseif ($node instanceof DocumentType) {
                $s .= $this->printDocumentType($node);
                $appendNewLine = true;
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
                    $s .= ' ' . $this->getAttrString($attr);
                }
                if (ElementNode::isVoid($name)) {
                    $s .= ' />';
                    if ($this->willPutNewLineAfterEndTag($node)) {
                        $appendNewLine = true;
                    }
                } else {
                    $s .= '>';
                    if ($node->hasChildNodes()) {
                        if ($this->willPutNewLineAfterStartTag($node)) {
                            $s .= "\n";
                        }
                        $toPrepend = [];
                        if ($node->namespaceURI() === DomNs::HTML && $name === 'html') {
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
                            $appendNewLine = true;
                        }
                    }
                }
            } else { // end tag case
                /** @var ElementNode $tag */
                $tag = $queueItem[2];
                $name = $tag->localName();
                if ($tag->namespaceURI() === DomNs::HTML && (TextOnlyElementNode::match($name) || $name === 'pre')) {
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
                    $appendNewLine = true;
                }
            }
        }
        return $s;
    }

    protected function getIndent(int $depth): string
    {
        if (isset($this->indentCache[$depth])) {
            return $this->indentCache[$depth];
        }
        if ($depth <= 0) {
            $this->indentCache = ['', $this->option->indent];
            return '';
        }
        $s = $this->getIndent($depth - 1, $this->option) . $this->option->indent;
        $this->indentCache[$depth] = $s;
        return $s;
    }

    protected function getAttrString(Attr $attr): string
    {
        $n = $attr->name();
        $v = $attr->value();
        $s = $n;
        if ($v === '' && AttrNode::isBoolean($n)) {
            // skip value output
        } else {
            $s .= '=';
            $quote = '"';
            if ($this->option->escAttrValue) {
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

    protected function isInlineElement(Element $element): bool
    {
        return $element->namespaceURI() === DomNs::HTML && in_array($element->localName(), [
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
        if ($element->namespaceURI() === DomNs::HTML) {
            return !$this->isInlineElement($element);
        }
        return true;
    }

    protected function willPutNewLineAfterStartTag(Element $element): bool
    {
        if ($element->namespaceURI() === DomNs::HTML) {
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
