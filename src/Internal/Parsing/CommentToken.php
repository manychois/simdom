<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\CommentNode;

/**
 * Represents a comment token.
 */
class CommentToken extends AbstractToken
{
    public readonly CommentNode $node;

    /**
     * Creates a new instance of CommentToken.
     *
     * @param string $text The text of the token.
     */
    public function __construct(string $text)
    {
        parent::__construct(TokenType::Comment);
        $this->node = new CommentNode($text);
    }
}
