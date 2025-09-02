<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParseState;

/**
 * Parses a HTML string into a DOM structure.
 */
final class HtmlParser
{
    /**
     * Parses a HTML string into a Document.
     *
     * @param string $html the HTML string to parse
     *
     * @return Document the parsed Document
     */
    public function parseDocument(string $html): Document
    {
        $doc = Document::create();
        $ps = new ParseState($html, $doc);
        $ps->parse();

        return $doc;
    }

    /**
     * Parses a HTML string into a Fragment.
     *
     * @param string $html    the HTML string to parse
     * @param string $context the context element tag name
     *
     * @return Fragment the parsed Fragment
     */
    public function parseFragment(string $html, string $context = ''): Fragment
    {
        $frag = Fragment::create();
        if ('' === $context) {
            $ps = new ParseState($html, $frag);
            $ps->parse();
        } else {
            $virtaulElement = Element::create($context);
            $ps = new ParseState($html, $virtaulElement);
            $ps->parse();
            $nodes = $virtaulElement->childNodes->asArray();
            $virtaulElement->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Clear();
            $frag->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙InsertAt(0, ...$nodes);
        }

        return $frag;
    }

    // region internal methods

    /**
     * Changes the inner HTML of a parent node.
     *
     * @param AbstractParentNode $parent the parent node whose inner HTML is to be changed
     * @param string             $html   the new HTML content
     *
     * @internal
     */
    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙ChangeInnerHtml(AbstractParentNode $parent, string $html): void
    {
        $parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Clear();
        $ps = new ParseState($html, $parent);
        $ps->parse();
    }

    // endregion internal methods
}
