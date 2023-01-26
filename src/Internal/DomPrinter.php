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
use Manychois\Simdom\NodeList;
use Manychois\Simdom\PrintOption;
use Manychois\Simdom\Text;

class DomPrinter
{
    public function print(Node $node, PrintOption $option): string
    {
        if ($node instanceof Comment) {
            return implode("\n", $this->getCommentLines($node, $option));
        } elseif ($node instanceof Document) {
            return implode("\n", $this->getChildNodesLines($node->childNodes(), $option, false, false, false));
        } elseif ($node instanceof DocumentFragment) {
            return implode("\n", $this->getChildNodesLines($node->childNodes(), $option, false, false, false));
        } elseif ($node instanceof DocumentType) {
            return $this->getDocumentTypeString($node, $option);
        } elseif ($node instanceof Element) {
            return implode("\n", $this->getElementLines($node, $option));
        } else {
            $rawTextMode = false;
            $pre = false;
            $parent = $node->parentNode();
            while ($parent instanceof Element) {
                $name = $parent->localName();
                $isHtml = $parent->namespaceURI() === DomNs::Html;
                if (!$rawTextMode) {
                    $rawTextMode = $isHtml && in_array($name, [
                        'style', 'xmp', 'iframe', 'noembed', 'noframes', 'script', 'noscript', 'template',
                    ], true);
                }
                if (!$pre) {
                    $pre = $isHtml && $name === 'pre';
                }
                if ($rawTextMode && $pre) {
                    break;
                }
            }
            return implode("\n", $this->getTextLines($node, $option, $rawTextMode, $pre));
        }
    }

    public function getAttrString(Attr $attr, PrintOption $option): string
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

    /**
     * @return array<string>
     */
    public function getChildNodesLines(
        NodeList $nodes,
        PrintOption $option,
        bool $isInline,
        bool $rawTextMode,
        bool $pre
    ): array {
        if ($isInline || !$option->prettyPrint) {
            $s = '';
            foreach ($nodes as $node) {
                if ($rawTextMode && !$node instanceof Text) {
                    continue;
                }
                if ($node instanceof Comment) {
                    foreach ($this->getCommentLines($node, $option) as $line) {
                        $s .= $line;
                    }
                } elseif ($node instanceof DocumentType) {
                    $s .= $this->getDocumentTypeString($node, $option);
                } elseif ($node instanceof Element) {
                    foreach ($this->getElementLines($node, $option) as $line) {
                        $s .= $line;
                    }
                } elseif ($node instanceof Text) {
                    foreach ($this->getTextLines($node, $option, $rawTextMode, $pre) as $line) {
                        $s .= $line;
                    }
                }
            }
            if ($isInline) {
                return explode("\n", $s);
            } else {
                return [$s];
            }
        }

        $lines = [];
        foreach ($nodes as $node) {
            if ($node instanceof Comment) {
                $lines = array_merge($lines, $this->getCommentLines($node, $option));
            } elseif ($node instanceof DocumentType) {
                $lines[] = $this->getDocumentTypeString($node, $option);
            } elseif ($node instanceof Element) {
                $lines = array_merge($lines, $this->getElementLines($node, $option));
            } elseif ($node instanceof Text) {
                $lines = array_merge($lines, $this->getTextLines($node, $option, $rawTextMode, $pre));
            }
        }
        return $lines;
    }

    /**
     * @return array<string>
     */
    public function getCommentLines(Comment $comment, PrintOption $option): array
    {
        if (!$option->prettyPrint) {
            return ['<!--' . $comment->data() . '-->'];
        }
        $lines = explode("\n", trim($comment->data()));
        if (count($lines) === 1) {
            return ['<!-- ' . $lines[0] . ' -->'];
        } else {
            for ($i = 0; $i < count($lines); $i++) {
                $lines[$i] = trim($lines[$i]);
            }
            return ['<!--', ...$lines, '-->'];
        }
    }

    public function getDocumentTypeString(DocumentType $docType, PrintOption $option): string
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

    /**
     * @return array<string>
     */
    public function getElementLines(Element $element, PrintOption $option): array
    {
        $name = $element->localName();
        $s = '<' . $name;
        $hasAttr = $element->attributes()->length() > 0;
        $isHtml = $element->namespaceURI() === DomNs::Html;
        if ($hasAttr) {
            $sa = [];
            foreach ($element->attributes() as $attr) {
                $sa[] = $this->getAttrString($attr, $option);
            }
            $s .= ' ' . implode(' ', $sa);
        }
        if ($isHtml && ElementNode::isVoid($name)) {
            if ($option->selfClosingSlash) {
                $s .= $hasAttr ? ' />' : '/>';
            } else {
                $s .= '>';
            }
            return [$s];
        }

        $lines = [$s . '>'];
        if ($element->childNodes()->length() > 0) {
            $isInline = $this->isInlineElement($element);
            $rawTextMode = $isHtml && in_array($name, [
                'style', 'xmp', 'iframe', 'noembed', 'noframes', 'script', 'noscript', 'template',
            ], true);
            $pre = $isHtml && $name === 'pre';
            $childLines = $this->getChildNodesLines($element->childNodes(), $option, $isInline, $rawTextMode, $pre);
            if ($isInline && count($childLines) === 1) {
                $lines[0] .= $childLines[0] . '</' . $name . '>';
            } else {
                if (!$option->prettyPrint || $isHtml && $name === 'html') {
                    $lines = array_merge($lines, $childLines);
                } else {
                    foreach ($childLines as $childLine) {
                        $lines[] = $option->indent . $childLine;
                    }
                }
                $lines[] = '</' . $name . '>';
            }
        } else {
            $lines[] = '</' . $name . '>';
        }
        return $lines;
    }

    /**
     * @return array<string>
     */
    public function getTextLines(Text $text, PrintOption $option, bool $rawTextMode, bool $pre): array
    {
        if ($rawTextMode) {
            return [$text->data()];
        }
        if ($pre || !$option->prettyPrint) {
            return [BaseNode::escapeString($text->data())];
        }
        $lines = explode("\n", BaseNode::escapeString($text->data()));
        $count = count($lines);
        if ($count === 1) {
            return [preg_replace('/\s+/', ' ', $lines[0])];
        }
        for ($i = 0; $i < $count; ++$i) {
            $line = preg_replace('/\s+/', ' ', $lines[$i]);
            if ($i > 0) {
                $line = ltrim($line);
            }
            if ($i < $count) {
                $line = rtrim($line);
            }
            $lines[$i] = $line;
        }
        return $lines;
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
}
