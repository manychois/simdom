<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\StringStream;
use Manychois\Simdom\NamespaceUri;

/**
 * Represents the tokenization logic of a string of HTML.
 */
class Lexer
{
    private readonly DomParser $parser;
    private readonly TokenEmitter $emitter;
    private readonly StringStream $str;
    private readonly LexerCommentLogic $commentLogic;
    private readonly LexerTagLogic $tagLogic;

    /**
     * Creates a new Lexer instance.
     *
     * @param DomParser $parser The parser that will receive the tokens.
     */
    public function __construct(DomParser $parser)
    {
        $this->parser = $parser;
        $this->emitter = new TokenEmitter($parser);
        $this->str = new StringStream();
        $this->commentLogic = new LexerCommentLogic($this->str, $this->emitter);
        $this->tagLogic = new LexerTagLogic($this->str, $this->emitter);
    }

    /**
     * Initializes the lexer with the given HTML string.
     *
     * @param string $html The HTML string to be tokenized.
     */
    public function setInput(string $html): void
    {
        /** @var string $html */
        $html = preg_replace('/\r\n?/', "\n", $html);
        $this->str->reset($html);
        $this->emitter->reset();
    }

    /**
     * Skips the next newline character if it exists.
     */
    public function skipNextNewline(): void
    {
        $chr = $this->str->current();
        if ($chr === "\n") {
            $this->str->advance();
        }
    }

    /**
     * Tokenizes the given HTML string.
     * The lexer must be initialized with `setInput()` before calling this method.
     * The parser will receive 0-2 tokens for each call to this method.
     *
     * @return bool Whether there are more tokens to be parsed.
     */
    public function tokenize(): bool
    {
        $chr = $this->str->current();
        if ($chr === '<') {
            $this->str->advance();
            $this->tokenizeTagOpen();
        } elseif ($chr === '') {
            $this->emitter->emit(new EofToken());
        } else {
            $pos = $this->str->findNextStr('<');
            if ($pos < 0) {
                $text = LexerLiteralUtility::decodeHtml($this->str->readToEnd());
                $this->emitter->emit(new TextToken($text));
                $this->emitter->emit(new EofToken());
            } else {
                $text = LexerLiteralUtility::decodeHtml($this->str->read($pos));
                $this->emitter->emit(new TextToken($text));
            }
        }

        return !$this->emitter->isEofEmitted();
    }

    /**
     * Returns the raw text between the current position and the given end tag.
     * The lexer will advance the current position to the end of the end tag.
     *
     * @param string $endTagName The target end tag name.
     *
     * @return string The raw text between the current position and the given end tag.
     */
    public function tokenizeRawText(string $endTagName): string
    {
        $pattern = '/(.*?)' . preg_quote("</$endTagName", '/') . '/is';
        $matchResult = $this->str->regexMatch($pattern);
        if ($matchResult->success) {
            $text = $matchResult->captures[0];
            $this->str->advance(strlen($matchResult->value));
        } else { // consume until EOF
            $text = $this->str->readToEnd();
        }

        $temp = new EndTagToken($endTagName);
        $this->tagLogic->tokenizeAllAttrs($temp);
        $chr = $this->str->current();
        if ($chr === '/') {
            $this->str->advance(2); // consume "/>"
        } elseif ($chr === '>') {
            $this->str->advance();
        }

        return $text;
    }

    /**
     * Returns the decoded text between the current position and the given end tag.
     * The lexer will advance the current position to the end of the end tag.
     *
     * @param string $endTagName The target end tag name.
     *
     * @return string The decoded text between the current position and the given end tag.
     */
    public function tokenizeRcdataText(string $endTagName): string
    {
        $text = $this->tokenizeRawText($endTagName);

        return LexerLiteralUtility::decodeHtml(LexerLiteralUtility::fixNull($text));
    }

    /**
     * Tokenizes in the doctype state.
     */
    private function tokenizeDoctype(): void
    {
        $pos = $this->str->findNextStr('>');
        if ($pos < 0) {
            $part = $this->str->readToEnd();
        } else {
            $part = $this->str->read($pos);
            $this->str->advance();
        }

        preg_match('/\s*(\S*)\s*/', $part, $matches);
        $name = LexerLiteralUtility::fixNull($matches[1]);
        $part = substr($part, strlen($matches[0]));
        $lookForPublicId = false;
        $publicId = '';
        $systemId = '';

        $isMatch = preg_match('/^(PUBLIC|SYSTEM)\s*/i', $part, $matches);
        if ($isMatch === 1) {
            $part = substr($part, strlen($matches[0]));
            $keyword = strtoupper($matches[1]);
            if ($keyword === 'PUBLIC') {
                $lookForPublicId = true;
            } else {
                $systemId = self::consumeSystemId($part);
            }
        }

        if ($lookForPublicId) {
            $chr = $part[0] ?? '';
            if ($chr === '"' || $chr === '\'') {
                $isMatch = preg_match("/^$chr([^$chr]*)$chr?\s*/", $part, $match);
                if ($isMatch === 1) {
                    $part = substr($part, strlen($match[0]));
                    $publicId = LexerLiteralUtility::fixNull($match[1]);
                    $systemId = self::consumeSystemId($part);
                }
            }
        }

        $this->emitter->emit(new DoctypeToken($name, $publicId, $systemId));
    }

    /**
     * Extracts the system ID from the given part of the doctype.
     *
     * @param string $part The part of the doctype to be tokenized.
     *
     * @return string The system identifier, or empty string if not found.
     */
    private static function consumeSystemId(string $part): string
    {
        $systemId = '';
        $chr = $part[0] ?? '';
        if ($chr === '"' || $chr === '\'') {
            $isMatch = preg_match("/^$chr([^$chr]*)/", $part, $match);
            if ($isMatch === 1) {
                $systemId = LexerLiteralUtility::fixNull($match[1]);
            }
        }

        return $systemId;
    }

    /**
     * Tokenizes in the markup declaration open state.
     */
    private function tokenizeMarkupDeclarationOpen(): void
    {
        $first2 = $this->str->peek(2);
        if ($first2 === '--') {
            $this->str->advance(2);
            $this->commentLogic->tokenizeComment();
        } elseif ($first2 === '[C') {
            $this->str->advance(2);
            $next5 = $this->str->peek(5);
            if ($next5 === 'DATA[') {
                $this->str->advance(5);
                if (
                    $this->parser->mode === InsertionMode::ForeignContent &&
                    $this->parser->currentNode()->namespaceUri() !== NamespaceUri::Html
                ) {
                    $this->commentLogic->tokenizeCdata();
                } else {
                    $this->commentLogic->tokenizeBogusComment('[CDATA[');
                }
            } else {
                $this->commentLogic->tokenizeBogusComment($first2);
            }
        } elseif (strcasecmp($first2, 'DO') === 0) {
            $this->str->advance(2);
            $next5 = $this->str->peek(5);
            if (strcasecmp($next5, 'CTYPE') === 0) {
                $this->str->advance(5);
                $this->tokenizeDoctype();
            } else {
                $this->commentLogic->tokenizeBogusComment($first2);
            }
        } else {
            $this->commentLogic->tokenizeBogusComment();
        }
    }

    /**
     * Tokenizes in the tag open state.
     */
    private function tokenizeTagOpen(): void
    {
        $chr = $this->str->current();
        if ($chr >= 'a' && $chr <= 'z' || $chr >= 'A' && $chr <= 'Z') {
            $this->tagLogic->tokenizeStartTag();
        } elseif ($chr === '/') {
            $this->str->advance();
            if (!$this->tagLogic->tokenizeEndTagOpen()) {
                $this->commentLogic->tokenizeBogusComment();
            }
        } elseif ($chr === '!') {
            $this->str->advance();
            $this->tokenizeMarkupDeclarationOpen();
        } elseif ($chr === '?') {
            $this->str->advance();
            $this->commentLogic->tokenizeBogusComment('?');
        } else {
            $this->emitter->emit(new TextToken('<'));
        }
    }
}
