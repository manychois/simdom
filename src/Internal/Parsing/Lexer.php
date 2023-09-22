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
    private bool $eofEmitted = false;
    private StringStream $str;

    /**
     * Creates a new Lexer instance.
     *
     * @param DomParser $parser The parser that will receive the tokens.
     */
    public function __construct(DomParser $parser)
    {
        $this->parser = $parser;
        $this->str = new StringStream('');
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
        $this->str = new StringStream($html);
        $this->eofEmitted = false;
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
            $this->emit(new EofToken());
        } else {
            $pos = $this->str->findNextStr('<');
            if ($pos < 0) {
                $text = self::decodeHtml($this->str->readToEnd());
                $this->emit(new TextToken($text));
                $this->emit(new EofToken());
            } else {
                $text = self::decodeHtml($this->str->readTo($pos));
                $this->emit(new TextToken($text));
            }
        }

        return !$this->eofEmitted;
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
        $this->tokenizeAllAttrs($temp);
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

        return self::decodeHtml(self::fixNull($text));
    }

    /**
     * Converts HTML entities to their corresponding UTF-8 characters.
     *
     * @param string $str The input string.
     *
     * @return string The decoded string.
     */
    private static function decodeHtml(string $str): string
    {
        return html_entity_decode($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Replaces null characters with the Unicode replacement character.
     *
     * @param string $str The input string.
     *
     * @return string The fixed string.
     */
    private static function fixNull(string $str): string
    {
        return str_replace("\0", "\u{FFFD}", $str);
    }

    /**
     * Emits a token to the parser.
     *
     * @param AbstractToken $token The token to be emitted.
     */
    private function emit(AbstractToken $token): void
    {
        $this->parser->receiveToken($token);
        if ($token instanceof EofToken) {
            $this->eofEmitted = true;
        }
    }

    /**
     * Moves the current position to the next attribute name.
     *
     * @return bool True if there is attribute name to be tokenized.
     */
    private function moveToAttrName(): bool
    {
        $chr = $this->str->current();
        if (ctype_space($chr)) {
            $this->str->advance();

            return true;
        }

        if ($chr === '' || $chr === '>') {
            return false;
        }

        if ($chr === '/') {
            $peek = $this->str->peek(2);
            if ($peek === '/>') {
                return false;
            }

            $this->str->advance();

            return true;
        }

        return true;
    }

    /**
     * Tokenizes all the attributes into the current tag.
     *
     * @param StartTagToken|EndTagToken $token The token to be populated with the attributes.
     */
    private function tokenizeAllAttrs(StartTagToken|EndTagToken $token): void
    {
        while ($this->tokenizeAttr($token));
    }

    /**
     * Tokenizes in attribute name and value states.
     *
     * @param StartTagToken|EndTagToken $token The token to be populated with the attribute.
     *
     * @return bool True if there are more attributes to be tokenized.
     */
    private function tokenizeAttr(StartTagToken|EndTagToken $token): bool
    {
        if (!$this->moveToAttrName()) {
            return false;
        }

        $name = $this->tokenizeAttrName();
        // capture optional "=" which sits between the attribute name and value
        $matchResult = $this->str->regexMatch('/\s*(\=?)\s*/');
        $this->str->advance(strlen($matchResult->value));
        $value = $matchResult->captures[0] === '=' ? $this->tokenizeAttrValue() : null;

        if ($name !== '' && $token instanceof StartTagToken) {
            if (!$token->node->hasAttribute($name)) {
                $token->node->setAttribute($name, $value);
            }
        }

        return true;
    }

    /**
     * Tokenizes in the attribute name state.
     *
     * @return string The attribute name generated by the tokenization.
     */
    private function tokenizeAttrName(): string
    {
        $chr = $this->str->current();
        $name = '';
        if ($chr === '=') {
            $this->str->advance();
            $name = '=';
        }
        $matchResult = $this->str->regexMatch('/[^\s\/>=]*/');
        $this->str->advance(strlen($matchResult->value));
        $name .= strtolower(self::fixNull($matchResult->value));

        return $name;
    }

    /**
     * Tokenizes in the attribute value state.
     *
     * @return string The attribute value generated by the tokenization.
     */
    private function tokenizeAttrValue(): string
    {
        $chr = $this->str->current();
        if ($chr === '"') {
            $matchResult = $this->str->regexMatch('/"([^"]*)"?\s*/s');
        } elseif ($chr === "'") {
            $matchResult = $this->str->regexMatch('/\'([^\']*)\'?\s*/s');
        } else { // unquoted version
            $matchResult = $this->str->regexMatch('/([^\s>]*)\s*/');
        }
        assert($matchResult->success);
        $this->str->advance(strlen($matchResult->value));

        return self::decodeHtml(self::fixNull($matchResult->captures[0]));
    }

    /**
     * Tokenizes in the bogus comment state.
     *
     * @param string $data The initial text data for the comment.
     */
    private function tokenizeBogusComment(string $data = ''): void
    {
        $pos = $this->str->findNextStr('>');
        if ($pos < 0) {
            $data .= $this->str->readToEnd();
        } else {
            $data .= $this->str->readTo($pos);
            $this->str->advance();
        }
        $this->emit(new CommentToken($data));

        // TODO
        if (!$this->str->hasNext()) {
            $this->emit(new EofToken());
        }
    }

    /**
     * Tokenizes in the CDATA section state.
     */
    private function tokenizeCdata(): void
    {
        $pos = $this->str->findNextStr(']]>');
        if ($pos < 0) { // CDATA without end
            $data = $this->str->readToEnd();
            $this->emit(new TextToken($data));
            $this->emit(new EofToken());
        } else {
            $data = $this->str->readTo($pos);
            $this->str->advance(3);
            $this->emit(new TextToken($data));
        }
    }

    /**
     * Tokenizes in the comment state.
     *
     * @param string $initData The initial data of the comment.
     */
    private function tokenizeComment(string $initData = ''): void
    {
        $pos = $this->str->findNextStr('>');
        if ($pos < 0) { // comment without end
            $data = $this->str->readToEnd();
            $this->emit(new CommentToken($initData . $data));
            $this->emit(new EofToken());
        } else {
            $data = $this->str->readTo($pos);
            $this->str->advance();
            if ($data === '' || $data === '-') { // <!--> or <!---> case
                $this->emit(new CommentToken($initData));
            } elseif (substr($data, -2) === '--') { // correct --> case
                $this->emit(new CommentToken($initData . substr($data, 0, -2)));
            } else { // stay in the comment state
                $initData .= $data . '>';
                $this->tokenizeComment($initData);
            }
        }
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
            $part = $this->str->readTo($pos);
            $this->str->advance();
        }

        preg_match('/\s*(\S*)\s*/', $part, $matches);
        $name = self::fixNull($matches[1]);
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
                $systemId = $this->consumeSystemId($part);
            }
        }

        if ($lookForPublicId) {
            $chr = $part[0] ?? '';
            if ($chr === '"' || $chr === '\'') {
                $isMatch = preg_match("/^$chr([^$chr]*)$chr?\s*/", $part, $match);
                if ($isMatch === 1) {
                    $part = substr($part, strlen($match[0]));
                    $publicId = self::fixNull($match[1]);
                    $systemId = $this->consumeSystemId($part);
                }
            }
        }

        $this->emit(new DoctypeToken($name, $publicId, $systemId));
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
                $systemId = self::fixNull($match[1]);
            }
        }

        return $systemId;
    }

    /**
     * Tokenizes in the end tag open state.
     */
    private function tokenizeEndTagOpen(): void
    {
        $chr = $this->str->current();
        if ($chr >= 'a' && $chr <= 'z' || $chr >= 'A' && $chr <= 'Z') {
            $tagName = $this->tokenizeTagName();
            $token = new EndTagToken($tagName);
            $this->tokenizeAllAttrs($token);
            $chr = $this->str->current();
            if ($chr === '/') {
                $this->str->advance(2); // consume "/>"
                $this->emit($token);
            } elseif ($chr === '>') {
                $this->str->advance();
                $this->emit($token);
            } else {
                $this->emit(new EofToken());
            }
        } elseif ($chr === '>') {
            $this->str->advance();
        } elseif ($chr === '') {
            $this->emit(new TextToken('</'));
            $this->emit(new EofToken());
        } else {
            $this->tokenizeBogusComment();
        }
    }

    /**
     * Tokenizes in the markup declaration open state.
     */
    private function tokenizeMarkupDeclarationOpen(): void
    {
        $first2 = $this->str->peek(2);
        if ($first2 === '--') {
            $this->str->advance(2);
            $this->tokenizeComment();
        } elseif ($first2 === '[C') {
            $this->str->advance(2);
            $next5 = $this->str->peek(5);
            if ($next5 === 'DATA[') {
                $this->str->advance(5);
                if (
                    $this->parser->mode === InsertionMode::ForeignContent &&
                    $this->parser->currentNode()->namespaceUri() !== NamespaceUri::Html
                ) {
                    $this->tokenizeCdata();
                } else {
                    $this->tokenizeBogusComment('[CDATA[');
                }
            } else {
                $this->tokenizeBogusComment($first2);
            }
        } elseif (strcasecmp($first2, 'DO') === 0) {
            $this->str->advance(2);
            $next5 = $this->str->peek(5);
            if (strcasecmp($next5, 'CTYPE') === 0) {
                $this->str->advance(5);
                $this->tokenizeDoctype();
            } else {
                $this->tokenizeBogusComment($first2);
            }
        } else {
            $this->tokenizeBogusComment();
        }
    }

    /**
     * Tokenizes in the start tag state.
     */
    private function tokenizeStartTag(): void
    {
        $tagName = $this->tokenizeTagName();
        $token = new StartTagToken($tagName);
        $this->tokenizeAllAttrs($token);
        $chr = $this->str->current();
        if ($chr === '/') {
            $this->str->advance(2); // consume "/>"
            $token->selfClosing = true;
            $this->emit($token);
        } elseif ($chr === '>') {
            $this->str->advance();
            $this->emit($token);
        } else {
            $this->emit(new EofToken());
        }
    }

    /**
     * Tokenizes in the tag open state.
     */
    private function tokenizeTagOpen(): void
    {
        $chr = $this->str->current();
        if ($chr >= 'a' && $chr <= 'z' || $chr >= 'A' && $chr <= 'Z') {
            $this->tokenizeStartTag();
        } elseif ($chr === '/') {
            $this->str->advance();
            $this->tokenizeEndTagOpen();
        } elseif ($chr === '!') {
            $this->str->advance();
            $this->tokenizeMarkupDeclarationOpen();
        } elseif ($chr === '?') {
            $this->str->advance();
            $this->tokenizeBogusComment('?');
        } else {
            $this->emit(new TextToken('<'));
        }
    }

    /**
     * Tokenizes in the tag name state.
     * The first character should have been verified to be a letter.
     *
     * @return string The tag name generated by the tokenization.
     */
    private function tokenizeTagName(): string
    {
        $matchResult = $this->str->regexMatch('/[^\s\/>]+/');
        assert($matchResult->success);
        $tagName = strtolower(self::fixNull($matchResult->value));
        $this->str->advance(strlen($matchResult->value));

        return $tagName;
    }
}
