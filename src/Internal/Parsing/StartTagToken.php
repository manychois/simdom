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
}
