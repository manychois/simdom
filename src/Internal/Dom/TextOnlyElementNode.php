<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

/**
 * Represents an element node that contains only text.
 */
class TextOnlyElementNode extends ElementNode
{
    /**
     * Returns true if the given local name represents a text-only element.
     *
     * @param string $localName The local name to check.
     *
     * @return bool True if the given local name represents a text-only element.
     */
    public static function isTextOnly(string $localName): bool
    {
        return in_array($localName, [
            'noframes',
            'noscript',
            'script',
            'style',
            'template',
            'textarea',
            'title',
            // obsolete
            'noembed',
            'xmp',
        ]);
    }

    /**
     * Creates a text-only element node based on the given element node.
     *
     * @param ElementNode $node The element node to copy.
     */
    public function __construct(ElementNode $node)
    {
        parent::__construct($node->localName());
        $this->attrs = $node->attrs;
    }
}
