<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\ElementNode;

/**
 * Represents a start tag token.
 */
class StartTagToken extends AbstractToken
{
    public readonly ElementNode $node;
    public bool $selfClosing = false;

    /**
     * Creates a new start tag token.
     *
     * @param string $tagName The tag name.
     */
    public function __construct(string $tagName)
    {
        parent::__construct(TokenType::StartTag);
        $this->node = new ElementNode($tagName);
    }

        /**
     * Returns whether the tag name is one of the given tag names.
     *
     * @param string ...$tagNames The tag names to check.
     *
     * @return bool Whether the tag name is one of the given tag names.
     */
    public function isOneOf(string ...$tagNames): bool
    {
        return in_array($this->node->localName(), $tagNames, true);
    }
}
