<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\Internal\Dom\TextNode;
use Manychois\Simdom\NamespaceUri;

/**
 * Represents the logic of the "in body" insertion mode.
 */
class InBodyInsertionLogic extends AbstractInsertionLogic
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(InsertionMode::InBody);
    }

    #region extends AbstractInsertionLogic

    /**
     * @inheritDoc
     */
    public function run(DomParser $parser, AbstractToken $token): void
    {
        if ($token instanceof TextToken) {
            $token->node->setData(str_replace("\0", '', $token->node->data()));
            if ($token->node->data() !== '') {
                $parser->currentNode()->fastAppend($token->node);
            }
        } elseif ($token instanceof CommentToken) {
            $parser->currentNode()->fastAppend($token->node);
        } elseif ($token instanceof StartTagToken) {
            $this->handleStartTagToken($parser, $token);
        } elseif ($token instanceof EndTagToken) {
            $this->handleEndTagToken($parser, $token);
        }
    }

    #endregion

    /**
     * Handles an end tag token.
     *
     * @param DomParser   $parser The DOM parser to execute the logic on.
     * @param EndTagToken $token  The token to process.
     */
    private function handleEndTagToken(DomParser $parser, EndTagToken $token): void
    {
        if ($token->tagName === 'body') {
            if ($this->findStackIndex($parser, fn (ElementNode $ele) => $ele->tagName() === 'BODY') < 0) {
                // ignore
            } else {
                $parser->mode = InsertionMode::AfterBody;
            }
        } elseif ($token->tagName === 'html') {
            if ($this->findStackIndex($parser, fn (ElementNode $ele) => $ele->tagName() === 'BODY') < 0) {
                // ignore
            } else {
                $parser->mode = InsertionMode::AfterBody;
            }
            $parser->processTokenByMode($token);
        } else {
            $idx = $this->findStackIndex($parser, fn (ElementNode $ele) => $ele->localName() === $token->tagName);
            if ($idx < 0) {
                // ignore
            } else {
                array_splice($parser->stack, $idx);
            }
        }
    }

    /**
     * Handles a start tag token.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param StartTagToken $token  The token to process.
     */
    private function handleStartTagToken(DomParser $parser, StartTagToken $token): void
    {
        match ($token->node->localName()) {
            'base',
            'basefont',
            'bgsound',
            'command',
            'link',
            'meta',
            'noframes',
            'script',
            'style',
            'template',
            'title' => $parser->processTokenByMode($token, InsertionMode::InHead),
            'body' => $this->handleBodyTag($parser, $token),
            'head' => 0,
            'html' => $parser->fillMissingAttrs($token, $parser->stack[0]),
            'iframe',
            'noembed',
            'noscript',
            'xmp' => $this->handleRawtextTag($parser, $token),
            'image' => $parser->insertForeignElement($token->swapTagName('img')),
            'math' => $this->handleMathTag($parser, $token),
            'listing',
            'pre' => $this->handleListingOrPre($parser, $token),
            'svg' => $this->handleSvgTag($parser, $token),
            'textarea' => $this->handleTextarea($parser, $token),
            default => $parser->insertForeignElement($token),
        };
    }

    /**
     * Handles a body tag.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param StartTagToken $token  The token to process.
     */
    private function handleBodyTag(DomParser $parser, StartTagToken $token): void
    {
        $eBody = $parser->stack[1] ?? null;
        if ($eBody !== null && $eBody->tagName() === 'BODY') {
            $parser->fillMissingAttrs($token, $eBody);
        }
    }

    /**
     * Handles a math tag.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param StartTagToken $token  The token to process.
     */
    private function handleMathTag(DomParser $parser, StartTagToken $token): void
    {
        $parser->insertForeignElement($token, NamespaceUri::MathMl);
        $parser->mode = InsertionMode::ForeignContent;
    }

    /**
     * Handles a listing or pre tag.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param StartTagToken $token  The token to process.
     */
    private function handleListingOrPre(DomParser $parser, StartTagToken $token): void
    {
        $parser->lexer->skipNextNewline();
        $parser->insertForeignElement($token);
    }

    /**
     * Handles tags which contains only raw text.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param StartTagToken $token  The token to process.
     */
    private function handleRawtextTag(DomParser $parser, StartTagToken $token): void
    {
        $ele = $parser->insertForeignElement($token);
        $ele->fastAppend(new TextNode($parser->lexer->tokenizeRawText($ele->localName())));
    }

    /**
     * Handles a svg tag.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param StartTagToken $token  The token to process.
     */
    private function handleSvgTag(DomParser $parser, StartTagToken $token): void
    {
        $parser->insertForeignElement($token, NamespaceUri::Svg);
        $parser->mode = InsertionMode::ForeignContent;
    }

    /**
     * Handles a textarea tag.
     *
     * @param DomParser     $parser The DOM parser to execute the logic on.
     * @param StartTagToken $token  The token to process.
     */
    private function handleTextarea(DomParser $parser, StartTagToken $token): void
    {
        $parser->lexer->skipNextNewline();
        $eTextarea = $parser->insertForeignElement($token);
        $eTextarea->fastAppend(new TextNode($parser->lexer->tokenizeRcdataText('textarea')));
    }
}
