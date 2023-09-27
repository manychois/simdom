<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Emits tokens to the parser.
 */
class TokenEmitter
{
    private readonly DomParser $parser;
    private bool $eofEmitted = false;

    /**
     * Constructor.
     *
     * @param DomParser $parser The parser to receive the tokens.
     */
    public function __construct(DomParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Emits a token to the parser.
     *
     * @param AbstractToken $token The token to be emitted.
     */
    public function emit(AbstractToken $token): void
    {
        $this->parser->receiveToken($token);
        if ($token instanceof EofToken) {
            $this->eofEmitted = true;
        }
    }

    /**
     * Resets the state of the token emitter.
     */
    public function reset(): void
    {
        $this->eofEmitted = false;
    }

    /**
     * Returns true if the EOF token has been emitted.
     *
     * @return bool True if the EOF token has been emitted.
     */
    public function isEofEmitted(): bool
    {
        return $this->eofEmitted;
    }
}
