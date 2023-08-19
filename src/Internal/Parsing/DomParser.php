<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Closure;
use Manychois\Simdom\Internal\Dom\DocNode;
use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\Internal\Dom\NonHtmlElementNode;
use Manychois\Simdom\Internal\Dom\TextNode;
use Manychois\Simdom\Internal\Dom\TextOnlyElementNode;
use Manychois\Simdom\Internal\Dom\VoidElementNode;
use Manychois\Simdom\Internal\NamespaceUri;
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

    private bool $isFragmentMode = false;

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
            InsertionMode::InBody => $this->runInBodyInsertionMode($token),
            InsertionMode::AfterBody => $this->runAfterBodyInsertionMode($token),
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
            $this->headPointer = $this->insertForeignElement($headTag);
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
                $this->insertForeignElement($token);
            } elseif ($token->node->localName() === 'title') {
                $eTitle = $this->insertForeignElement($token);
                $eTitle->fastAppend(new TextNode($this->lexer->tokenizeRcdataText('title')));
            } elseif ($token->isOneOf('noframes', 'noscript', 'script', 'style', 'template')) {
                $e = $this->insertForeignElement($token);
                $e->fastAppend(new TextNode($this->lexer->tokenizeRawText($e->localName())));
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
            $this->insertForeignElement($bodyTag);
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
                    'title',
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

    /**
     * Runs the in body insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runInBodyInsertionMode(AbstractToken $token): void
    {
        if ($token instanceof TextToken) {
            $token->node->setData(str_replace("\0", '', $token->node->data()));
            if ($token->node->data() !== '') {
                $this->currentNode()->fastAppend($token->node);
            }
        } elseif ($token instanceof CommentToken) {
            $this->currentNode()->fastAppend($token->node);
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->fillMissingAttrs($token, $this->stack[0]);
            } elseif (
                $token->isOneOf(
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
                    'title',
                )
            ) {
                $this->runInHeadInsertionMode($token);
            } elseif ($token->node->localName() === 'body') {
                $eBody = $this->stack[1] ?? null;
                if ($eBody?->tagName() === 'BODY') {
                    $this->fillMissingAttrs($token, $eBody);
                }
            } elseif ($token->isOneOf('pre', 'listing')) {
                $this->lexer->skipNextNewline();
                $this->insertForeignElement($token);
            } elseif ($token->node->localName() === 'image') {
                $this->insertForeignElement($token->swapTagName('img'));
            } elseif ($token->node->localName() === 'textarea') {
                $this->lexer->skipNextNewline();
                $eTextarea = $this->insertForeignElement($token);
                $eTextarea->fastAppend(new TextNode($this->lexer->tokenizeRcdataText('textarea')));
            } elseif ($token->isOneOf('xmp', 'iframe', 'noembed', 'noscript')) {
                $e = $this->insertForeignElement($token);
                $e->fastAppend(new TextNode($this->lexer->tokenizeRawText($e->localName())));
            } elseif ($token->node->localName() === 'math') {
                $this->insertForeignElement($token, NamespaceUri::MathMl);
            } elseif ($token->node->localName() === 'svg') {
                $this->insertForeignElement($token, NamespaceUri::Svg);
            } elseif ($token->node->localName() === 'head') {
                // ignore
            } else {
                $this->insertForeignElement($token);
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'body') {
                if ($this->findStackIndex(fn (ElementNode $e) => $e->tagName() === 'BODY') < 0) {
                    // ignore
                } else {
                    $this->mode = InsertionMode::AfterBody;
                }
            } elseif ($token->tagName === 'html') {
                if ($this->findStackIndex(fn (ElementNode $e) => $e->tagName() === 'BODY') < 0) {
                    // ignore
                } else {
                    $this->mode = InsertionMode::AfterBody;
                }
                $this->processTokenByMode($token);
            } else {
                $i = $this->findStackIndex(fn (ElementNode $e) => $e->localName() === $token->tagName);
                if ($i < 0) {
                    // ignore
                } else {
                    array_splice($this->stack, $i);
                }
            }
        }
    }

    /**
     * Runs the after body insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runAfterBodyInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        if ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->runInBodyInsertionMode(new TextToken($matches[1]));
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->stack[0]->fastAppend($token->node);
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->runInBodyInsertionMode($token);
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'html') {
                if ($this->isFragmentMode) {
                    // ignore
                } else {
                    $this->mode = InsertionMode::AfterAfterBody;
                }
            } else {
                $fallback = true;
            }
        } elseif ($token->type === TokenType::Eof) {
            // stop parsing
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $this->mode = InsertionMode::InBody;
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the after after body insertion mode.
     * 
     * @param AbstractToken $token The token to process.
     */
    private function runAfterAfterBodyInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        if ($token instanceof CommentToken) {
            $this->doc->fastAppend($token->node);
        } elseif ($token instanceof DoctypeToken) {
            $this->runInBodyInsertionMode($token);
        } elseif ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->runInBodyInsertionMode(new TextToken($matches[1]));
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->runInBodyInsertionMode($token);
            } else {
                $fallback = true;
            }
        } elseif ($token->type === TokenType::Eof) {
            // stop parsing
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $this->mode = InsertionMode::InBody;
            $this->processTokenByMode($token);
        }
    }

    #endregion

    /**
     * Inserts the attributes of the given token into the given element, if the element does not have the attributes.
     *
     * @param StartTagToken    $token The token to get the attributes from.
     * @param null|ElementNode $e     The element to insert the attributes into.
     */
    private function fillMissingAttrs(StartTagToken $token, ?ElementNode $e): void
    {
        if ($e === null) {
            return;
        }

        foreach ($token->node->attributes() as $k => $v) {
            if (!$e->hasAttribute($k)) {
                $e->setAttribute($k, $v);
            }
        }
    }

    /**
     * Finds the index of the first element in the stack that matches the given predicate.
     * The search starts from the end of the stack.
     *
     * @param Closure $predicate The predicate to match.
     *
     * @return int The index of the first matching element, or -1 if no element matches.
     */
    private function findStackIndex(Closure $predicate): int
    {
        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if ($predicate($this->stack[$i])) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Inserts a foreign element into the current node.
     *
     * @param StartTagToken $token The token to get the element from.
     * @param NamespaceUri  $ns    The namespace of the element.
     *
     * @return ElementNode The inserted element.
     */
    private function insertForeignElement(StartTagToken $token, NamespaceUri $ns = NamespaceUri::Html): ElementNode
    {
        $pushToStack = !$token->selfClosing;
        $e = $token->node;
        $localName = $e->localName();
        if ($ns === NamespaceUri::Html) {
            if (VoidElementNode::isVoid($localName)) {
                $e = new VoidElementNode($e);
                $pushToStack = false;
            } elseif (TextOnlyElementNode::isTextOnly($localName)) {
                $e = new TextOnlyElementNode($e);
                $pushToStack = false;
            }
        } else {
            $e = new NonHtmlElementNode($e, $ns);
        }
        $this->currentNode()->fastAppend($e);
        if ($pushToStack) {
            $this->stack[] = $e;
        }

        return $e;
    }
}
