<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Parsing;

use Manychois\Simdom\Parsing\Parser;
use Manychois\Simdom\Parsing\Token;

class TestLexerParser extends Parser
{
    /**
     * @var array<Token>
     */
    public array $emitted = [];

    public function simdomTreeConstruct(Token $token): void
    {
        $this->emitted[] = $token;
    }
}
