<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

use Manychois\Simdom\Converters\DomConverter;
use Manychois\Simdom\Document;
use Manychois\Simdom\Internal\ElementKind;
use Manychois\Simdom\Text;

/**
 * Parses an HTML document using the native PHP DOM parser.
 */
class DomParser
{
    /**
     * Parses an HTML document.
     *
     * @param string $html The HTML document to parse.
     *
     * @return Document The parsed document.
     */
    public function parseDocument(string $html): Document
    {
        $original = \libxml_use_internal_errors(true);
        try {
            $domDoc = new \DOMDocument();
            $domDoc->loadHTML($html);
            $converter = new DomConverter();

            return $converter->toDocument($domDoc);
        } finally {
            \libxml_use_internal_errors($original);
        }
    }

    /**
     * Parses a fragment of an HTML document.
     *
     * @param string $html    The HTML fragment to parse.
     * @param string $context The tag name of the element that will contain the fragment.
     *
     * @return array<int,\Manychois\Simdom\AbstractNode> The parsed nodes.
     */
    public function parsePartial(string $html, string $context = 'body'): array
    {
        $context = \strtolower($context);
        $kind = ElementKind::identify($context);
        if ($kind === ElementKind::Void) {
            return [];
        }

        if ($kind === ElementKind::RawText) {
            return [new Text($html)];
        }

        if ($kind === ElementKind::EscapableRawText) {
            $html = \html_entity_decode($html, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'utf-8');

            return [new Text($html)];
        }

        $html = \sprintf('<%1$s>%2$s</%1$s>', $context, $html);
        $doc = $this->parseDocument($html);
        $target = null;
        foreach ($doc->descendantElements() as $ele) {
            if ($ele->tagName === $context) {
                $target = $ele;

                break;
            }
        }

        if ($target === null) {
            return [];
        }

        $parsedNodes = $target->childNodeList->toArray();
        $target->clear();

        return $parsedNodes;
    }
}
