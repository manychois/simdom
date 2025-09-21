<?php

declare(strict_types=1);

namespace Manychois\Simdom;

class PrettyPrinter
{
    protected const BEFORE_NODE = 1;
    protected const AFTER_NODE = 2;
    protected const AFTER_OPEN_TAG = 4;
    protected const BEFORE_CLOSE_TAG = 8;

    protected const INLINE_ELEMENTS = [
        'a',
        'abbr',
        'acronym',
        'audio',
        'b',
        'bdi',
        'bdo',
        'big',
        'br',
        'cite',
        'code',
        'data',
        'dfn',
        'em',
        'font',
        'i',
        'img',
        'kbd',
        'mark',
        'q',
        'rb',
        'rp',
        'rt',
        'rtc',
        'ruby',
        's',
        'samp',
        'small',
        'span',
        'strike',
        'strong',
        'sub',
        'sup',
        'time',
        'tt',
        'u',
        'var',
        'wbr',
    ];

    public function print(AbstractNode $node): string
    {
        $cloned = $node->clone(true);
        if ($cloned instanceof Document || $cloned instanceof Fragment) {
            $cloned->normalise();
            $this->formatDocOrFragment($cloned);

            return $cloned->__toString();
        }

        $fragment = Fragment::create();
        $fragment->append($cloned);
        $fragment->normalise();
        $this->formatDocOrFragment($fragment);

        return $cloned->__toString();
    }

    protected function formatDocOrFragment(Document|Fragment $parent): void
    {
        $childNodes = $parent->childNodes->asArray();
        foreach ($childNodes as $node) {
            $this->addNewline($node, 0, self::BEFORE_NODE | self::AFTER_NODE);
            if ($node instanceof Element) {
                $this->formatElement($node, 0);
            }
        }
        $firstChild = $parent->childNodes->at(0);
        if ($firstChild instanceof Text) {
            $firstChild->data = ltrim($firstChild->data);
            if ('' === $firstChild->data) {
                $firstChild->remove();
            }
        }
    }

    protected function formatElement(Element $element, int $indent): void
    {
        if (in_array($element->name, self::INLINE_ELEMENTS, true)) {
            return;
        }

        $this->addNewline($element, $indent, self::BEFORE_NODE);
        $noInnerFormatting = $element->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsVoid
            || in_array($element->name, ['pre', 'textarea'], true);

        if ($noInnerFormatting) {
            $this->addNewline($element, $indent, self::AFTER_NODE);
        } else {
            $noAddIndent = in_array($element->name, ['html', 'script', 'style'], true);
            $nextIdent = $noAddIndent ? $indent : $indent + 1;
            $this->addNewline($element, $nextIdent, self::AFTER_OPEN_TAG);
            foreach ($element->childNodes as $child) {
                if ($child instanceof Element) {
                    $this->formatElement($child, $nextIdent);
                }
            }
            $this->addNewline($element, $indent, self::BEFORE_CLOSE_TAG | self::AFTER_NODE);
            $childCount = $element->childNodes->count();
            if (1 === $childCount) {
                $child = $element->childNodes->at(0);
                if ($child instanceof Text) {
                    $data = trim($child->data);
                    if (strlen($data) < 30) {
                        $child->data = $data;
                    }
                    if ('' === $child->data) {
                        $child->remove();
                    }
                }
            }
        }
    }

    protected function addNewline(AbstractNode $node, int $indent, int $flag): void
    {
        $indentStr = str_repeat(' ', $indent * 2);

        if ($flag & self::BEFORE_NODE) {
            if ($node instanceof Text) {
                $node->data = "\n{$indentStr}" . ltrim($node->data);
            } else {
                $before = $node->previousSibling;
                if ($before instanceof Text) {
                    $before->data = rtrim($before->data) . "\n{$indentStr}";
                } else {
                    $node->before("\n{$indentStr}");
                }
            }
        }

        if ($flag & self::AFTER_NODE) {
            if ($node instanceof Text) {
                $node->data = rtrim($node->data) . "\n{$indentStr}";
            } else {
                $after = $node->nextSibling;
                if ($after instanceof Text) {
                    $after->data = "\n{$indentStr}" . ltrim($after->data);
                } else {
                    $node->after("\n{$indentStr}");
                }
            }
        }

        if (!($node instanceof AbstractParentNode)) {
            return;
        }

        if ($flag & self::AFTER_OPEN_TAG) {
            $firstChild = $node->childNodes->at(0);
            if ($firstChild instanceof Text) {
                $firstChild->data = "\n{$indentStr}" . ltrim($firstChild->data);
            } else {
                $node->prepend("\n{$indentStr}");
            }
        }

        if ($flag & self::BEFORE_CLOSE_TAG) {
            $lastChild = $node->childNodes->at(-1);
            if ($lastChild instanceof Text) {
                $lastChild->data = rtrim($lastChild->data) . "\n{$indentStr}";
            } else {
                $node->append("\n{$indentStr}");
            }
        }
    }
}
