<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Represents the base class for all tokens.
 */
abstract class AbstractToken
{
    public readonly TokenType $type;

    /**
     * Initializes a new instance of the AbstractToken class.
     *
     * @param TokenType $type The type of the token.
     */
    public function __construct(TokenType $type)
    {
        $this->type = $type;
    }
}
