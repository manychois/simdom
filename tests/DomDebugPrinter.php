<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use Manychois\Simdom\Comment;
use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\ParentNode;
use Manychois\Simdom\Node;
use Manychois\Simdom\Text;

class DomDebugPrinter
{
    public function print(Document $doc): string
    {
        $lines = [];
        $lines[] = $this->getNodeLine($doc);
        foreach ($this->printChildNodes($doc) as $childLine) {
            $lines[] = $childLine;
        }
        return implode("\n", $lines);
    }

    /**
     * @return array<string>
     */
    private function printElement(Element $element): array
    {
        $lines = [];
        $lines[] = $this->getNodeLine($element);
        foreach ($this->printChildNodes($element) as $childLine) {
            $lines[] = $childLine;
        }
        return $lines;
    }

    /**
     * @return array<string>
     */
    private function printChildNodes(ParentNode $parent): array
    {
        $lines = [];
        $n = $parent->childNodes()->length();
        foreach ($parent->childNodes() as $i => $child) {
            $hasNext = $i < $n - 1;
            $connector = $hasNext ? '├─' : '└─';
            if ($child instanceof Element) {
                foreach ($this->printElement($child) as $childLine) {
                    if ($childLine[0] === '<') {
                        $lines[] = $connector . $childLine;
                    } else {
                        $lines[] = ($hasNext ? '│ ' : '  ') . $childLine;
                    }
                }
            } else {
                $lines[] = $connector . $this->getNodeLine($child);
            }
        }
        return $lines;
    }

    private function getNodeLine(Node $n): string
    {
        switch ($n->nodeType()) {
            case Node::COMMENT_NODE:
                assert($n instanceof Comment);
                return sprintf('#comment %s', $this->str($n->data()));
            case Node::DOCUMENT_TYPE_NODE:
                assert($n instanceof DocumentType);
                return sprintf(
                    '#doctype name:%s, publicId:%s, systemId:%s',
                    $this->str($n->name()),
                    $this->str($n->publicId()),
                    $this->str($n->systemId())
                );
            case Node::DOCUMENT_NODE:
                return '#document';
            case Node::ELEMENT_NODE:
                assert($n instanceof Element);
                return rtrim(sprintf('<%s> %s', $n->tagName(), $this->getAttrsLine($n)));
            case Node::TEXT_NODE:
                assert($n instanceof Text);
                return sprintf('#text %s', $this->str($n->data()));
        };
    }

    private function getAttrsLine(Element $element): string
    {
        $attrs = $element->attributes();
        $result = [];
        foreach ($attrs as $attr) {
            $result[] = sprintf('%s:%s', $attr->name(), $this->str($attr->value()));
        }
        return implode(', ', $result);
    }

    private function str(?string $s): string
    {
        if ($s === null) {
            return 'null';
        }
        return sprintf('"%s"', str_replace("\n", '⏎', str_replace('"', '\"', $s)));
    }
}
