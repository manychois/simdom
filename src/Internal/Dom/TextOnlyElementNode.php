<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

/**
 * Represents an element node that contains only text.
 */
class TextOnlyElementNode extends ElementNode
{
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
