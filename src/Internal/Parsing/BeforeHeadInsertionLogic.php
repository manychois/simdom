<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Represents the logic of the "before head" insertion mode.
 */
class BeforeHeadInsertionLogic extends AbstractInsertionLogic
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(InsertionMode::BeforeHead);
    }

    #region extends AbstractInsertionLogic

    /**
     * @inheritDoc
     */
    public function run(DomParser $parser, AbstractToken $token): void
    {
        $fallback = false;

        if ($token instanceof TextToken) {
            if (ctype_space($token->node->data())) {
                // ignore
            } else {
                $token->node->setData(ltrim($token->node->data()));
                $fallback = true;
            }
        } elseif ($this->handleCommentAndDoctype($parser, $token)) {
            // processed by handleCommentAndDoctype
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $parser->processTokenByMode($token, InsertionMode::InBody);
            } elseif ($token->node->localName() === 'head') {
                $this->handleStartTagToken($parser, $token);
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            $fallback = $token->isOneOf('head', 'body', 'html', 'br');
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $this->handleStartTagToken($parser, new StartTagToken('head'));
            $parser->processTokenByMode($token);
        }
    }

    #endregion

    /**
     * The normal action for handling start tag tokens.
     *
     * @param DomParser     $parser  The DOM parser to execute the logic on.
     * @param StartTagToken $headTag The token to process.
     */
    private function handleStartTagToken(DomParser $parser, StartTagToken $headTag): void
    {
        $parser->headPointer = $parser->insertForeignElement($headTag);
        $parser->mode = InsertionMode::InHead;
    }
}
