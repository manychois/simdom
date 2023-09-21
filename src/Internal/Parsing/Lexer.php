<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\NamespaceUri;

/**
 * Represents the tokenization logic of a string of HTML.
 */
class Lexer
{
    private readonly DomParser $parser;
    private string $src = '';
    private int $len = 0;
    private int $pos = 0;
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
        $this->src = $html;
        $this->len = strlen($this->src);
        $this->pos = 0;
        $this->eofEmitted = false;
    }

    /**
     * Skips the next newline character if it exists.
     */
    public function skipNextNewline(): void
    {
        $chr = $this->src[$this->pos] ?? '';
        if ($chr === "\n") {
            ++$this->pos;
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
        if ($this->pos >= 1024) { // reduce memory usage
            $this->src = substr($this->src, $this->pos);
            $this->len = strlen($this->src);
            $this->pos = 0;
        }

        $chr = $this->src[$this->pos] ?? '';
        if ($chr === '<') {
            ++$this->pos;
            $this->tokenizeTagOpen();
        } elseif ($chr === '') {
            $this->emit(new EofToken());
        } else {
            $pos = strpos($this->src, '<', $this->pos);
            if ($pos === false) {
                $text = self::decodeHtml(substr($this->src, $this->pos));
                $this->pos = $this->len;
                $this->emit(new TextToken($text));
                $this->emit(new EofToken());
            } else {
                $text = self::decodeHtml(substr($this->src, $this->pos, $pos - $this->pos));
                $this->pos = $pos;
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
        $isMatch = preg_match($pattern, $this->src, $matches, 0, $this->pos);
        if ($isMatch === 1) {
            $text = $matches[1];
            $this->pos += strlen($matches[0]);
        } else { // consume until EOF
            $text = substr($this->src, $this->pos);
            $this->pos = $this->len;
        }

        $temp = new EndTagToken($endTagName);
        $this->tokenizeAllAttrs($temp);
        $chr = $this->src[$this->pos] ?? '';
        if ($chr === '/') {
            $this->pos += 2; // consume "/>"
        } elseif ($chr === '>') {
            ++$this->pos;
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
        $chr = $this->src[$this->pos] ?? '';
        if (ctype_space($chr)) {
            ++$this->pos;

            return true;
        }

        if ($chr === '' || $chr === '>') {
            return false;
        }

        if ($chr === '/') {
            $nextChr = $this->src[$this->pos + 1] ?? '';
            if ($nextChr === '>') {
                return false;
            }
            ++$this->pos;

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
        preg_match('/\s*(\=?)\s*/', $this->src, $matches, 0, $this->pos);
        $this->pos += strlen($matches[0]);
        $value = $matches[1] === '=' ? $this->tokenizeAttrValue() : null;

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
        $chr = $this->src[$this->pos] ?? '';
        $name = '';
        if ($chr === '=') {
            ++$this->pos;
            $name = '=';
        }
        preg_match('/[^\s\/>=]*/', $this->src, $matches, 0, $this->pos);
        $this->pos += strlen($matches[0]);
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
        $chr = $this->src[$this->pos] ?? '';
        if ($chr === '"') {
            preg_match('/"([^"]*)"?\s*/s', $this->src, $matches, 0, $this->pos);
            $this->pos += strlen($matches[0]);
            $value = $matches[1];
        } elseif ($chr === "'") {
            preg_match('/\'([^\']*)\'?\s*/s', $this->src, $matches, 0, $this->pos);
            $this->pos += strlen($matches[0]);
            $value = $matches[1];
        } else { // unquoted version
            preg_match('/([^\s>]*)\s*/', $this->src, $matches, 0, $this->pos);
            $this->pos += strlen($matches[0]);
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
        $gtPos = strpos($this->src, '>', $this->pos);
        if ($gtPos !== false) {
            $data .= substr($this->src, $this->pos, $gtPos - $this->pos);
            $this->pos = $gtPos + 1;
            $this->emit(new CommentToken($data));
        } else {
            $data .= substr($this->src, $this->pos);
            $this->pos = $this->len;
            $this->emit(new CommentToken($data));
            $this->emit(new EofToken());
        }
    }

    /**
     * Tokenizes in the CDATA section state.
     */
    private function tokenizeCdata(): void
    {
        $pos = strpos($this->src, ']]>', $this->pos);
        if ($pos === false) { // CDATA without end
            $data = substr($this->src, $this->pos);
            $this->pos = $this->len;
            $this->emit(new TextToken($data));
            $this->emit(new EofToken());
        } else {
            $data = substr($this->src, $this->pos, $pos - $this->pos);
            $this->pos = $pos + 3;
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
        $gtPos = strpos($this->src, '>', $this->pos);
        if ($gtPos === false) { // comment without end
            $data = substr($this->src, $this->pos);
            $this->pos = $this->len;
            $this->emit(new CommentToken($initData . $data));
            $this->emit(new EofToken());
        } else {
            $data = substr($this->src, $this->pos, $gtPos - $this->pos);
            $this->pos = $gtPos + 1;
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
        $gtPos = strpos($this->src, '>', $this->pos);
        if ($gtPos === false) {
            $part = substr($this->src, $this->pos);
            $this->pos = $this->len;
        } else {
            $part = substr($this->src, $this->pos, $gtPos - $this->pos);
            $this->pos = $gtPos + 1;
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
    private function consumeSystemId(string $part): string
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
        $chr = $this->src[$this->pos] ?? '';
        if ($chr >= 'a' && $chr <= 'z' || $chr >= 'A' && $chr <= 'Z') {
            $tagName = $this->tokenizeTagName();
            $token = new EndTagToken($tagName);
            $this->tokenizeAllAttrs($token);
            $chr = $this->src[$this->pos] ?? '';
            if ($chr === '/') {
                $this->pos += 2; // consume "/>"
                $this->emit($token);
            } elseif ($chr === '>') {
                ++$this->pos;
                $this->emit($token);
            } else {
                $this->emit(new EofToken());
            }
        } elseif ($chr === '>') {
            ++$this->pos;
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
        $first2 = substr($this->src, $this->pos, 2);
        if ($first2 === '--') {
            $this->pos += 2;
            $this->tokenizeComment();
        } elseif ($first2 === '[C') {
            $this->pos += 2;
            $next5 = substr($this->src, $this->pos, 5);
            if ($next5 === 'DATA[') {
                $this->pos += 5;
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
            $this->pos += 2;
            $next5 = substr($this->src, $this->pos, 5);
            if (strcasecmp($next5, 'CTYPE') === 0) {
                $this->pos += 5;
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
        $chr = $this->src[$this->pos] ?? '';
        if ($chr === '/') {
            $this->pos += 2; // consume "/>"
            $token->selfClosing = true;
            $this->emit($token);
        } elseif ($chr === '>') {
            ++$this->pos;
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
        $chr = $this->src[$this->pos] ?? '';
        if ($chr >= 'a' && $chr <= 'z' || $chr >= 'A' && $chr <= 'Z') {
            $this->tokenizeStartTag();
        } elseif ($chr === '/') {
            ++$this->pos;
            $this->tokenizeEndTagOpen();
        } elseif ($chr === '!') {
            ++$this->pos;
            $this->tokenizeMarkupDeclarationOpen();
        } elseif ($chr === '?') {
            ++$this->pos;
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
        preg_match('/[^\s\/>]+/', $this->src, $matches, 0, $this->pos);
        $tagName = strtolower(self::fixNull($matches[0]));
        $this->pos += strlen($matches[0]);

        return $tagName;
    }
}
