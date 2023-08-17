<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\TextNode;

/**
 * Represents a text token.
 */
class TextToken extends AbstractToken
{
    public readonly TextNode $node;

    /**
     * Creates a new instance of TextToken.
     *
     * @param string $text The text of the token.
     */
    public function __construct(string $text)
    {
        parent::__construct(TokenType::Text);
        $this->node = new TextNode($text);
    }
}
