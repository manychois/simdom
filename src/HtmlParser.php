<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParseState;

final class HtmlParser
{
    public function parseDocument(string $html): Document
    {
        $doc = Document::create();
        $ps = new ParseState($html, $doc);
        $ps->parse();

        return $doc;
    }

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

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙ChangeInnerHtml(AbstractParentNode $parent, string $html): void
    {
        $parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Clear();
        $ps = new ParseState($html, $parent);
        $ps->parse();
    }

    // endregion internal methods
}
