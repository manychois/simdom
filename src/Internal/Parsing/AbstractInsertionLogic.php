<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Represents the logic of a certain insertion mode.
 */
abstract class AbstractInsertionLogic
{
    public readonly InsertionMode $mode;

    /**
     * Constructor.
     *
     * @param InsertionMode $mode The insertion mode.
     */
    public function __construct(InsertionMode $mode)
    {
        $this->mode = $mode;
    }

    /**
     * Runs the insertion logic.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param AbstractToken $token  The token to process.
     */
    abstract public function run(DomParser $parser, AbstractToken $token): void;

    /**
     * Finds the index of the first element in the stack that matches the given predicate.
     * The search starts from the end of the stack.
     *
     * @param DomParser $parser    The DOM parser which contains the stack.
     * @param callable  $predicate The predicate to match.
     *
     * @return int The index of the first matching element, or -1 if no element matches.
     */
    protected function findStackIndex(DomParser $parser, callable $predicate): int
    {
        for ($i = count($parser->stack) - 1; $i >= 0; $i--) {
            if ($predicate($parser->stack[$i])) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * The usual action for handling comment tokens and doctype tokens.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param AbstractToken $token  The token to process.
     *
     * @return bool True if the token is handled, false otherwise.
     */
    protected function handleCommentAndDoctype(DomParser $parser, AbstractToken $token): bool
    {
        if ($token instanceof CommentToken) {
            $parser->currentNode()->fastAppend($token->node);

            return true;
        }

        if ($token->type === TokenType::Doctype) {
            // do nothing
            return true;
        }

        return false;
    }
}
