<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Doctype;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Fragment;
use Manychois\Simdom\HtmlSerialiserInterface;
use Manychois\Simdom\Text;

class DefaultHtmlSerialiser implements HtmlSerialiserInterface
{
    public const array BOOLEAN_ATTRIBUTES = [
        'allowfullscreen',
        'async',
        'autofocus',
        'autoplay',
        'checked',
        'controls',
        'default',
        'defer',
        'disabled',
        'formnovalidate',
        'hidden',
        'inert',
        'ismap',
        'itemscope',
        'loop',
        'multiple',
        'muted',
        'nomodule',
        'novalidate',
        'open',
        'readonly',
        'required',
        'reversed',
        'selected',
        'truespeed',
        'typemustmatch',
    ];

    // region implements HtmlSerialiserInterface

    public function serialise(AbstractNode $node): string
    {
        if ($node instanceof Element) {
            return $this->serialiseElement($node);
        }
        if ($node instanceof Text) {
            return $this->serialiseText($node);
        }
        if ($node instanceof Comment) {
            return $this->serialiseComment($node);
        }
        if ($node instanceof Doctype) {
            return $this->serialiseDoctype($node);
        }
        if ($node instanceof Document) {
            return $this->serialiseDocument($node);
        }
        assert($node instanceof Fragment, 'Unsupported node type: ' . get_debug_type($node));

        return $this->serialiseFragment($node);
    }

    public function serialiseComment(Comment $comment): string
    {
        return sprintf('<!--%s-->', $comment->data);
    }

    public function serialiseDoctype(Doctype $doctype): string
    {
        $output = '<!DOCTYPE';
        if ('' !== $doctype->name) {
            $output .= ' ' . $doctype->name;
            $quote = static function (string $id): string {
                $q = str_contains($id, '"') ? '\'' : '"';

                return $q . $id . $q;
            };
            if ('' === $doctype->publicId) {
                if ('' !== $doctype->systemId) {
                    $output .= ' SYSTEM ' . $quote($doctype->systemId);
                }
            } else {
                $output .= ' PUBLIC ' . $quote($doctype->publicId);
                if ('' !== $doctype->systemId) {
                    $output .= ' ' . $quote($doctype->systemId);
                }
            }
        }
        $output .= '>';

        return $output;
    }

    public function serialiseDocument(Document $document): string
    {
        return $this->serialiseNodeList($document->childNodes);
    }

    public function serialiseElement(Element $element): string
    {
        $output = $this->serialiseOpenTag($element);
        if ($element->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsVoid) {
            return $output;
        }

        if ($element->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRawtext || $element->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRcdata) {
            $innerText = $this->serialiseNodeList($element->childNodes->filter(
                static fn (AbstractNode $node): bool => $node instanceof Text
            ));
            $innerText = str_replace('</' . $element->name, '&lt;/' . $element->name, $innerText);
            $output .= $innerText;
        } else {
            $output .= $this->serialiseNodeList($element->childNodes);
        }
        $output .= sprintf('</%s>', $element->name);

        return $output;
    }

    public function serialiseFragment(Fragment $fragment): string
    {
        return $this->serialiseNodeList($fragment->childNodes);
    }

    public function serialiseNodeList(iterable $list): string
    {
        $output = '';
        foreach ($list as $child) {
            $output .= $this->serialise($child);
        }

        return $output;
    }

    public function serialiseOpenTag(Element $element): string
    {
        $output = '<' . $element->name;
        foreach ($element->attrs() as $attrName => $attrValue) {
            if ('' === $attrValue && in_array($attrName, self::BOOLEAN_ATTRIBUTES, true)) {
                $output .= ' ' . $attrName;
                continue;
            }
            $output .= ' ' . $attrName . '=';
            $hasSingleQuote = false !== strpos($attrValue, '\'');
            $hasDoubleQuote = false !== strpos($attrValue, '"');
            $quote = !$hasSingleQuote && $hasDoubleQuote ? '\'' : '"';
            if ($hasDoubleQuote && '"' === $quote) {
                $attrValue = \str_replace('"', '&quot;', $attrValue);
            }
            $output .= $quote . $attrValue . $quote;
        }
        $output .= '>';

        return $output;
    }

    public function serialiseText(Text $text): string
    {
        $safeData = $text->data;
        $escLessThan = false;
        if ($text->parent instanceof Element) {
            $element = $text->parent;
            if ($element->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRawtext || $element->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRcdata) {
                $safeData = str_replace('</' . $element->name, '&lt;/' . $element->name, $safeData);
            } else {
                $escLessThan = true;
            }
        } else {
            $escLessThan = true;
        }

        if ($escLessThan) {
            $safeData = str_replace('<', '&lt;', $safeData);
        }

        return $safeData;
    }

    // endregion implements HtmlSerialiserInterface
}
