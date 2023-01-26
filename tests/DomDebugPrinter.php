<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use Manychois\Simdom\Comment;
use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\ParentNode;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeType;
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
        $s = fn (string $str) => $this->str($str);
        return match ($n->nodeType()) {
            NodeType::Comment => $n instanceof Comment ? sprintf('#comment %s', $s($n->data())) : '',
            NodeType::DocumentType => $n instanceof DocumentType ?
                sprintf(
                    '#doctype name:%s, publicId:%s, systemId:%s',
                    $s($n->name()),
                    $s($n->publicId()),
                    $s($n->systemId())
                ) : '',
            NodeType::Document => '#document',
            NodeType::Element => $n instanceof Element ?
                rtrim(sprintf('<%s> %s', $n->tagName(), $this->getAttrsLine($n))) : '',
            NodeType::Text => $n instanceof Text ? sprintf('#text %s', $s($n->data())) : '',
        };
    }

    private function getAttrsLine(Element $element): string
    {
        $s = fn (string $str) => $this->str($str);
        $attrs = $element->attributes();
        $result = [];
        foreach ($attrs as $attr) {
            $result[] = sprintf('%s:%s', $attr->name, $s($attr->value));
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
