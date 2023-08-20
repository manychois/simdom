<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\NamespaceUri;

/**
 * Represents the tokenization logic of a string of HTML.
 */
class Lexer
{
    private readonly DomParser $parser;
    private string $s;
    private int $len;
    private int $at;
    private bool $eofEmitted = false;

    /**
     * Creates a new Lexer instance.
     *
     * @param DomParser $parser The parser that will receive the tokens.
     */
    public function __construct(DomParser $parser)
    {
        $this->parser = $parser;
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
        $this->s = $html;
        $this->len = strlen($this->s);
        $this->at = 0;
        $this->eofEmitted = false;
    }

    /**
     * Skips the next newline character if it exists.
     */
    public function skipNextNewline(): void
    {
        $c = $this->s[$this->at] ?? '';
        if ($c === "\n") {
            ++$this->at;
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
        if ($this->at >= 1024) { // reduce memory usage
            $this->s = substr($this->s, $this->at);
            $this->len = strlen($this->s);
            $this->at = 0;
        }

        $c = $this->s[$this->at] ?? '';
        if ($c === '<') {
            ++$this->at;
            $this->tokenizeTagOpen();
        } elseif ($c === '') {
            $this->emit(new EofToken());
        } else {
            $pos = strpos($this->s, '<', $this->at);
            if ($pos === false) {
                $s = self::decodeHtml(substr($this->s, $this->at));
                $this->at = $this->len;
                $this->emit(new TextToken($s));
                $this->emit(new EofToken());
            } else {
                $s = self::decodeHtml(substr($this->s, $this->at, $pos - $this->at));
                $this->at = $pos;
                $this->emit(new TextToken($s));
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
        $pos = preg_match($pattern, $this->s, $matches, 0, $this->at);
        if ($pos) {
            $text = $matches[1];
            $this->at += strlen($matches[0]);
        } else { // consume until EOF
            $text = substr($this->s, $this->at);
            $this->at = $this->len;
        }

        $temp = new EndTagToken($endTagName);
        while ($this->tokenizeAttr($temp));
        $c = $this->s[$this->at] ?? '';
        if ($c === '/') {
            $this->at += 2; // consume "/>"
        } elseif ($c === '>') {
            ++$this->at;
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
     * @param string $s The input string.
     *
     * @return string The decoded string.
     */
    private static function decodeHtml(string $s): string
    {
        return html_entity_decode($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Replaces null characters with the Unicode replacement character.
     *
     * @param string $s The input string.
     *
     * @return string The fixed string.
     */
    private static function fixNull(string $s): string
    {
        return str_replace("\0", "\u{FFFD}", $s);
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
     * Tokenizes in attribute name and value states.
     *
     * @param StartTagToken|EndTagToken $token The token to be populated with the attribute.
     *
     * @return bool True if there are more attributes to be tokenized.
     */
    private function tokenizeAttr(StartTagToken|EndTagToken $token): bool
    {
        $c = $this->s[$this->at] ?? '';
        if (ctype_space($c)) {
            ++$this->at;

            return true;
        }

        if ($c === '' || $c === '>') {
            return false;
        }

        if ($c === '/') {
            $c1 = $this->s[$this->at + 1] ?? '';
            if ($c1 === '>') {
                return false;
            }
            ++$this->at;

            return true;
        }

        $name = $this->tokenizeAttrName();
        // capture optional "=" which sits between the attribute name and value
        preg_match('/\s*(\=?)\s*/', $this->s, $matches, 0, $this->at);
        $this->at += strlen($matches[0]);
        if ($matches[1] === '=') {
            $value = $this->tokenizeAttrValue();
        } else {
            $value = null;
        }

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
        $c = $this->s[$this->at] ?? '';
        $name = '';
        if ($c === '=') {
            ++$this->at;
            $name = '=';
        }
        preg_match('/[^\s\/>=]*/', $this->s, $matches, 0, $this->at);
        $this->at += strlen($matches[0]);
        $name .= strtolower(self::fixNull($matches[0]));

        return $name;
    }

    /**
     * Tokenizes in the attribute value state.
     *
     * @return string The attribute value generated by the tokenization.
     */
    private function tokenizeAttrValue(): string
    {
        $c = $this->s[$this->at] ?? '';
        $value = '';
        if ($c === '"') {
            preg_match('/"([^"]*)"?\s*/s', $this->s, $matches, 0, $this->at);
            $this->at += strlen($matches[0]);
            $value = $matches[1];
        } elseif ($c === "'") {
            preg_match('/\'([^\']*)\'?\s*/s', $this->s, $matches, 0, $this->at);
            $this->at += strlen($matches[0]);
            $value = $matches[1];
        } else { // unquoted version
            preg_match('/([^\s>]*)\s*/', $this->s, $matches, 0, $this->at);
            $this->at += strlen($matches[0]);
            $value = $matches[1];
        }

        return self::decodeHtml(self::fixNull($value));
    }

    /**
     * Tokenizes in the bogus comment state.
     *
     * @param string $data The initial text data for the comment.
     */
    private function tokenizeBogusComment(string $data = ''): void
    {
        $gt = strpos($this->s, '>', $this->at);
        if ($gt) {
            $data .= substr($this->s, $this->at, $gt - $this->at);
            $this->at = $gt + 1;
            $this->emit(new CommentToken($data));
        } else {
            $data .= substr($this->s, $this->at);
            $this->at = $this->len;
            $this->emit(new CommentToken($data));
            $this->emit(new EofToken());
        }
    }

    /**
     * Tokenizes in the CDATA section state.
     */
    private function tokenizeCdata(): void
    {
        $pos = strpos($this->s, ']]>', $this->at);
        if ($pos === false) { // CDATA without end
            $data = substr($this->s, $this->at);
            $this->at = $this->len;
            $this->emit(new TextToken($data));
            $this->emit(new EofToken());
        } else {
            $data = substr($this->s, $this->at, $pos - $this->at);
            $this->at = $pos + 3;
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
        $gt = strpos($this->s, '>', $this->at);
        if ($gt === false) { // comment without end
            $data = substr($this->s, $this->at);
            $this->at = $this->len;
            $this->emit(new CommentToken($initData . $data));
            $this->emit(new EofToken());
        } else {
            $data = substr($this->s, $this->at, $gt - $this->at);
            $this->at = $gt + 1;
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
        $gt = strpos($this->s, '>', $this->at);
        if ($gt === false) {
            $part = substr($this->s, $this->at);
            $this->at = $this->len;
        } else {
            $part = substr($this->s, $this->at, $gt - $this->at);
            $this->at = $gt + 1;
        }

        preg_match('/\s*(\S*)/', $part, $matches);
        $name = self::fixNull($matches[1]);
        $part = substr($part, strlen($matches[0]));
        $lookForPublicId = false;
        $lookForSystemId = false;
        $publicId = '';
        $systemId = '';

        if (preg_match('/^(PUBLIC|SYSTEM)\s*/i', $part, $matches)) {
            $part = substr($part, strlen($matches[0]));
            $keyword = strtoupper($matches[1]);
            if ($keyword === 'PUBLIC') {
                $lookForPublicId = true;
            } else {
                $lookForSystemId = true;
            }
        }

        if ($lookForPublicId) {
            $c = $part[0] ?? '';
            if ($c === '"' || $c === '\'') {
                if (preg_match("/^$c([^$c]*)$c?\s*/", $part, $match)) {
                    $part = substr($part, strlen($match[0]));
                    $publicId = self::fixNull($match[1]);
                    $lookForSystemId = true;
                }
            }
        }

        if ($lookForSystemId) {
            $c = $part[0] ?? '';
            if ($c === '"' || $c === '\'') {
                if (preg_match("/^$c([^$c]*)/", $part, $match)) {
                    $systemId = self::fixNull($match[1]);
                }
            }
        }

        $this->emit(new DoctypeToken($name, $publicId, $systemId));
    }

    /**
     * Tokenizes in the end tag open state.
     */
    private function tokenizeEndTagOpen(): void
    {
        $c = $this->s[$this->at] ?? '';
        if ($c >= 'a' && $c <= 'z' || $c >= 'A' && $c <= 'Z') {
            $tagName = $this->tokenizeTagName();
            $token = new EndTagToken($tagName);
            while ($this->tokenizeAttr($token));
            $c = $this->s[$this->at] ?? '';
            if ($c === '/') {
                $this->at += 2; // consume "/>"
                $this->emit($token);
            } elseif ($c === '>') {
                ++$this->at;
                $this->emit($token);
            } else {
                $this->emit(new EofToken());
            }
        } elseif ($c === '>') {
            ++$this->at;
        } elseif ($c === '') {
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
        $first2 = substr($this->s, $this->at, 2);
        if ($first2 === '--') {
            $this->at += 2;
            $this->tokenizeComment();
        } elseif ($first2 === '[C') {
            $this->at += 2;
            $next5 = substr($this->s, $this->at, 5);
            if ($next5 === 'DATA[') {
                $this->at += 5;
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
            $this->at += 2;
            $next5 = substr($this->s, $this->at, 5);
            if (strcasecmp($next5, 'CTYPE') === 0) {
                $this->at += 5;
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
        while ($this->tokenizeAttr($token));
        $c = $this->s[$this->at] ?? '';
        if ($c === '/') {
            $this->at += 2; // consume "/>"
            $token->selfClosing = true;
            $this->emit($token);
        } elseif ($c === '>') {
            ++$this->at;
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
        $c = $this->s[$this->at] ?? '';
        if ($c >= 'a' && $c <= 'z' || $c >= 'A' && $c <= 'Z') {
            $this->tokenizeStartTag();
        } elseif ($c === '/') {
            ++$this->at;
            $this->tokenizeEndTagOpen();
        } elseif ($c === '!') {
            ++$this->at;
            $this->tokenizeMarkupDeclarationOpen();
        } elseif ($c === '?') {
            ++$this->at;
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
        preg_match('/[^\s\/>]+/', $this->s, $matches, 0, $this->at);
        $tagName = strtolower(self::fixNull($matches[0]));
        $this->at += strlen($matches[0]);

        return $tagName;
    }
}
