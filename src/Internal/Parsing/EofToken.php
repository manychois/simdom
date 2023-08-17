<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Represents an end-of-file token.
 */
class EofToken extends AbstractToken
{
    /**
     * Creates an end-of-file token.
     */
    public function __construct()
    {
        parent::__construct(TokenType::Eof);
    }
}
