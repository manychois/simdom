<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\DocNode;
use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\Internal\Dom\TextNode;
use Manychois\Simdom\Internal\Dom\TextOnlyElementNode;
use Manychois\Simdom\Internal\Dom\VoidElementNode;
use RuntimeException;

/**
 * Represents a HTML DOM parser.
 */
class DomParser
{
    /**
     * @var array<int, ElementNode> The stack of open elements.
     */
    private array $stack = [];

    /**
     * @var InsertionMode The current insertion mode.
     */
    private InsertionMode $mode = InsertionMode::Initial;

    private Lexer $lexer;

    private DocNode $doc;

    private ?ElementNode $headPointer;

    /**
     * Returns the current node, i.e. the top node of the open elements stack.
     *
     * @return ElementNode The current element node.
     */
    public function currentNode(): ElementNode
    {
        $current = end($this->stack);
        if ($current === false) {
            throw new RuntimeException('The stack is empty.');
        }

        return $current;
    }

    /**
     * Parses the given HTML string into a document.
     *
     * @param string $html The HTML string to parse.
     *
     * @return DocNode The document node.
     */
    public function parse(string $html): DocNode
    {
        $doc = new DocNode();
        $this->doc = $doc;

        $this->mode = InsertionMode::Initial;
        $lexer = new Lexer($this);
        $this->lexer = $lexer;

        $lexer->setInput($html);
        while ($lexer->tokenize());

        $this->doc = new DocNode(); // assign a dummy doc
        $this->lexer->setInput(''); // reset the lexer

        return $doc;
    }

    /**
     * Receives a token from the tokenizer.
     *
     * @param AbstractToken $token The token to receive.
     */
    public function receiveToken(AbstractToken $token): void
    {
        $this->processTokenByMode($token);
    }

    /**
     * Processes a token according to the current insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    protected function processTokenByMode(AbstractToken $token): void
    {
        match ($this->mode) {
            InsertionMode::Initial => $this->runInitialInsertionMode($token),
            InsertionMode::BeforeHtml => $this->runBeforeHtmlInsertionMode($token),
            InsertionMode::BeforeHead => $this->runBeforeHeadInsertionMode($token),
            InsertionMode::InHead => $this->runInHeadInsertionMode($token),
            InsertionMode::AfterHead => $this->runAfterHeadInsertionMode($token),
        };
    }

    #region Insertion modes

    /**
     * Runs the initial insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runInitialInsertionMode(AbstractToken $token): void
    {
        $fallback = false;
        if ($token instanceof TextToken) {
            if (ctype_space($token->node->data())) {
                // ignore
            } else {
                $token->node->setData(ltrim($token->node->data()));
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->doc->fastAppend($token->node);
        } elseif ($token instanceof DoctypeToken) {
            $this->doc->fastAppend($token->node);
            $this->mode = InsertionMode::BeforeHtml;
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $this->mode = InsertionMode::BeforeHtml;
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the before html insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runBeforeHtmlInsertionMode(AbstractToken $token): void
    {
        $fallback = false;
        if ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof CommentToken) {
            $this->doc->fastAppend($token->node);
        } elseif ($token instanceof TextToken) {
            if (ctype_space($token->node->data())) {
                // ignore
            } else {
                $token->node->setData(ltrim($token->node->data()));
                $fallback = true;
            }
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->doc->fastAppend($token->node);
                $this->stack[] = $token->node;
                $this->mode = InsertionMode::BeforeHead;
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            $fallback = $token->isOneOf('head', 'body', 'html', 'br');
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $eHtml = new ElementNode('html');
            $this->doc->fastAppend($eHtml);
            $this->stack[] = $eHtml;
            $this->mode = InsertionMode::BeforeHead;
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the before head insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runBeforeHeadInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        $normalAction = function (StartTagToken $headTag) {
            $this->currentNode()->fastAppend($headTag->node);
            $this->headPointer = $headTag->node;
            $this->stack[] = $headTag->node;
            $this->mode = InsertionMode::InHead;
        };

        if ($token instanceof TextToken) {
            if (ctype_space($token->node->data())) {
                // ignore
            } else {
                $token->node->setData(ltrim($token->node->data()));
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->currentNode()->fastAppend($token->node);
        } elseif ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->runInBodyInsertionMode($token);
            } elseif ($token->node->localName() === 'head') {
                $normalAction($token);
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            $fallback = $token->isOneOf('head', 'body', 'html', 'br');
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $normalAction(new StartTagToken('head'));
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the in head insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runInHeadInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        $normalAction = function () {
            array_pop($this->stack);
            $this->mode = InsertionMode::AfterHead;
        };

        if ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->currentNode()->fastAppend(new TextNode($matches[1]));
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->currentNode()->fastAppend($token->node);
        } elseif ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->runInBodyInsertionMode($token);
            } elseif ($token->isOneOf('base', 'basefont', 'bgsound', 'command', 'link', 'meta')) {
                $this->currentNode()->fastAppend(new VoidElementNode($token->node));
            } elseif ($token->node->localName() === 'title') {
                $this->currentNode()->fastAppend($token->node);
                $token->node->fastAppend(new TextNode($this->lexer->tokenizeRcdataText('title')));
            } elseif ($token->isOneOf('noframes', 'noscript', 'script', 'style', 'template')) {
                $eNode = new TextOnlyElementNode($token->node);
                $this->currentNode()->fastAppend($eNode);
                $eNode->fastAppend(new TextNode($this->lexer->tokenizeRawText($eNode->localName())));
            } elseif ($token->node->localName() === 'head') {
                // ignore
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'head') {
                $normalAction();
            } else {
                $fallback = $token->isOneOf('body', 'html', 'br');
            }
        }

        if ($fallback) {
            $normalAction();
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the after head insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runAfterHeadInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        $normalAction = function (StartTagToken $bodyTag) {
            $this->currentNode()->fastAppend($bodyTag->node);
            $this->stack[] = $bodyTag->node;
            $this->mode = InsertionMode::InBody;
        };

        if ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->currentNode()->fastAppend(new TextNode($matches[1]));
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->currentNode()->fastAppend($token->node);
        } elseif ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->runInBodyInsertionMode($token);
            } elseif ($token->node->localName() === 'body') {
                $normalAction($token);
            } elseif (
                $token->isOneOf(
                    'base',
                    'basefont',
                    'bgsound',
                    'link',
                    'meta',
                    'noframes',
                    'script',
                    'style',
                    'template',
                    'title'
                )
            ) {
                assert($this->headPointer !== null);
                $this->stack[] = $this->headPointer;
                $this->runInHeadInsertionMode($token);
                array_pop($this->stack);
            } elseif ($token->node->localName() === 'head') {
                // ignore
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            $fallback = $token->isOneOf('body', 'html', 'br');
        }

        if ($fallback) {
            $normalAction(new StartTagToken('body'));
            $this->processTokenByMode($token);
        }
    }

    #endregion
}
