<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

use Manychois\Simdom\DomNs;

class Lexer
{
    public bool $trimNextLf = false;

    private readonly Parser $parser;
    private string $raw;
    private int $rawLen;
    private int $at;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function consumeRcDataText(string $endTagName): string
    {
        return html_entity_decode($this->consumeRawText($endTagName));
    }

    public function consumeRawText(string $endTagName): string
    {
        $pattern = '/' . preg_quote('</' . $endTagName, '/') . '([\s\/>])/i';
        if (preg_match($pattern, $this->raw, $matches, PREG_OFFSET_CAPTURE, $this->at)) {
            $text = substr($this->raw, $this->at, $matches[0][1] - $this->at);
            $this->at = $matches[1][1];
            $endTagToken = new TagToken($endTagName, false);
            while ($this->consumeAttr($endTagToken, true));
            $ch = $this->raw[$this->at] ?? '';
            if ($ch === '/') {
                $this->at += 2; // consume "/>"
            } elseif ($ch === '>') {
                $this->at++;
            } else {
                // EOF encountered
            }
        } else {
            $text = substr($this->raw, $this->at);
            $this->at = $this->rawLen;
        }
        return $text;
    }

    public function setInput(string $html, int $at): void
    {
        $this->raw = preg_replace('/\r\n?/', "\n", str_replace("\0", "\u{FFFD}", $html));
        $this->rawLen = strlen($this->raw);
        $this->at = $at;
    }

    public function stepTokenize(): void
    {
        $ch = $this->raw[$this->at] ?? '';
        if ($ch === '<') {
            $this->at++;
            $ch = $this->raw[$this->at] ?? '';
            if (ctype_alpha($ch)) {
                // The character will be consumed by the next state
                $this->consumeStartTag();
            } elseif ($ch === '/') {
                $this->at++;
                $this->consumeEndTag();
            } elseif ($ch === '!') {
                $this->at++;
                $first7 = substr($this->raw, $this->at, 7);
                $first2 = substr($first7, 0, 2);
                if ($first2 === '--') {
                    $this->at += 2;
                    $this->consumeComment('');
                } elseif ($first7 === '[CDATA[') {
                    $this->at += 7;
                    if ($this->parser->stack->current(true)->namespaceURI() === DomNs::Html) {
                        $this->consumeBogusComment('[CDATA[');
                    } else {
                        $this->consumeCdata();
                    }
                } elseif (strcasecmp($first7, 'DOCTYPE') === 0) {
                    $this->at += 7;
                    $this->consumeDoctype();
                } else {
                    $this->consumeBogusComment('');
                }
            } elseif ($ch === '?') {
                $this->consumeBogusComment('');
            } elseif ($ch === '') {
                $this->emit(new StringToken('<')); // emit "<" consumed earlier
                $this->emit(new EofToken());
            }
        } elseif ($ch === '') {
            $this->emit(new EofToken());
        } else {
            preg_match('/[^<]+/', $this->raw, $matches, 0, $this->at);
            $this->at += strlen($matches[0]);
            $token = new StringToken(html_entity_decode($matches[0]));
            $this->emit($token);
        }
    }

    public function tokenize(string $html): void
    {
        $this->setInput($html, 0);
        while (true) {
            $this->stepTokenize();
            if ($this->at >= $this->rawLen) {
                break;
            }
        }
    }

    protected function emit(Token $token): void
    {
        if ($this->trimNextLf && $token instanceof StringToken) {
            if ($token->value[0] === "\n") {
                $token->value = substr($token->value, 1);
            }
        }
        $this->trimNextLf = false;
        $this->parser->treeConstruct($token);
    }

    protected function consumeAttr(TagToken $token, bool $dropAttr = false): bool
    {
        $ch = $this->raw[$this->at] ?? '';
        if (ctype_space($ch)) {
            $this->at++;
            return true;
        }
        if ($ch === '' || $ch === '>') {
            return false;
        }
        if ($ch === '/') {
            $nextChar = $this->raw[$this->at + 1] ?? '';
            if ($nextChar === '>') {
                return false;
            } else {
                $this->at++;
                return true;
            }
        }

        if ($ch === '=') {
            $this->at++;
            $name = '=';
        } else {
            $name = '';
        }
        $value = '';

        // capture attribute name
        preg_match('/[^\s\/>=]*/', $this->raw, $matches, 0, $this->at);
        $this->at += strlen($matches[0]);
        $name .= strtolower($matches[0]);

        // capture "="
        preg_match('/\s*(\=?)\s*/', $this->raw, $matches, 0, $this->at);
        $this->at += strlen($matches[0]);
        if ($matches[1] === '=') {
            // capture attribute value
            $ch = $this->raw[$this->at] ?? '';
            if ($ch === '"' || $ch === "'") { // quoted version
                if (preg_match('/([\'"])(.*?)\1\s*/s', $this->raw, $matches, 0, $this->at)) {
                    $this->at += strlen($matches[0]);
                    $value = html_entity_decode($matches[2]);
                } else { // missing closing quote
                    $this->at = $this->rawLen;
                    return false; // skip attribute insertion
                }
            } else { // unquoted version
                preg_match('/([^\s>]*)\s*/', $this->raw, $matches, 0, $this->at);
                $this->at += strlen($matches[0]);
                $value = html_entity_decode($matches[1]);
            }
        } // else: it is an attribute without value specified

        if ($name !== '' && !$dropAttr) {
            // insert attribute into the token
            if (!array_key_exists($name, $token->attributes)) {
                $token->attributes[$name] = $value;
            }
        }
        return true;
    }

    protected function consumeBogusComment(string $data): void
    {
        $gt = strpos($this->raw, '>', $this->at);
        if ($gt) {
            $data .= substr($this->raw, $this->at, $gt - $this->at);
            $this->at = $gt + 1;
            $this->emit(new CommentToken($data));
        } else {
            $data .= substr($this->raw, $this->at);
            $this->at = $this->rawLen;
            $this->emit(new CommentToken($data));
            $this->emit(new EofToken());
        }
    }

    protected function consumeCdata(): void
    {
        if (preg_match('/(.*?)]]>/s', $this->raw, $matches, 0, $this->at)) {
            $capture = $matches[1];
            $this->at += strlen($capture) + 3;
            $this->emit(new StringToken($capture));
        } else {
            // CDATA without closing part
            $data = substr($this->raw, $this->at);
            $this->at = $this->rawLen;
            $this->emit(new StringToken($data));
            $this->emit(new EofToken());
        }
    }

    protected function consumeComment(string $data): void
    {
        if (preg_match('/(.*?)>/s', $this->raw, $matches, 0, $this->at)) {
            $capture = $matches[1];
            $this->at += strlen($capture) + 1;
            if ($capture === '' || $capture === '-') { // <!--> or <!---> case
                $this->emit(new CommentToken($data));
            } elseif (preg_match('/--!?$/', $capture, $matches)) { // Correct end --> found
                $data .= substr($capture, 0, -strlen($matches[0]));
                $this->emit(new CommentToken($data));
            } else {
                $data .= $capture . '>'; // Stay in comment state
                $this->consumeComment($data);
            }
        } else {
            // Comment without -->
            $data .= substr($this->raw, $this->at);
            $this->at = $this->rawLen;
            $this->emit(new CommentToken($data));
            $this->emit(new EofToken());
        }
    }

    protected function consumeDoctype(): void
    {
        preg_match('/[^>]*/', $this->raw, $matches, 0, $this->at);
        $this->at += strlen($matches[0]);
        $capture = trim($matches[0]);

        $doctype = new DoctypeToken();
        $detectKeyword = false;
        $tryPublicId = false;
        $trySystemId = false;
        while ($capture) {
            if ($doctype->name === null) {
                if (preg_match('/^(\S+)\s*/', $capture, $matches)) {
                    $doctype->name = strtolower($matches[1]);
                    $capture = substr($capture, strlen($matches[0]));
                    $detectKeyword = true;
                }
            }
            if ($detectKeyword) {
                $detectKeyword = false;
                if (preg_match('/^(PUBLIC|SYSTEM)\s*/i', $capture, $matches)) {
                    $capture = substr($capture, strlen($matches[0]));
                    if (strtoupper($matches[1]) === 'PUBLIC') {
                        $tryPublicId = true;
                    } else {
                        $trySystemId = true;
                    }
                }
            }
            if ($tryPublicId) {
                $tryPublicId = false;
                if (preg_match('/^([\'"])(.*?)\1\s*/s', $capture, $matches)) {
                    $doctype->publicId = $matches[2];
                    $capture = substr($capture, strlen($matches[0]));
                    $trySystemId = true;
                }
            }
            if ($trySystemId) {
                $trySystemId = false;
                if (preg_match('/^([\'"])(.*?)\1\s*/s', $capture, $matches)) {
                    $doctype->systemId = $matches[2];
                    $capture = substr($capture, strlen($matches[0]));
                }
            }
            break;
        }

        $ch = $this->raw[$this->at] ?? '';
        if ($ch === '') {
            $this->emit($doctype);
            $this->emit(new EofToken());
        } else { // Consume ">"
            $this->at++;
            $this->emit($doctype);
        }
    }

    /**
     * Builds and emits an end tag token.
     */
    protected function consumeEndTag(): void
    {
        $ch = $this->raw[$this->at] ?? '';
        if (ctype_alpha($ch)) {
            preg_match('/[^\s\/>]+/', $this->raw, $matches, 0, $this->at);
            $this->at += strlen($matches[0]);
            $token = new TagToken(strtolower($matches[0]), false);
            while ($this->consumeAttr($token, true));
            $ch = $this->raw[$this->at] ?? '';
            if ($ch === '/') {
                $this->at += 2; // consume "/>"
                $token->isSelfClosing = true;
                $this->emit($token);
            } elseif ($ch === '>') {
                $this->at++;
                $this->emit($token);
            } else { // EOF encountered
                $this->emit(new EofToken());
            }
        } elseif ($ch === '>') { // missing end tag name
            $this->at++;
        } elseif ($ch === '') {
            $this->emit(new StringToken('</'));
            $this->emit(new EofToken());
        } else {
            $this->consumeBogusComment('');
        }
    }

    /**
     * Builds and emits a start tag token.
     */
    protected function consumeStartTag(): void
    {
        preg_match('/[^\s\/>]+/', $this->raw, $matches, 0, $this->at);
        $this->at += strlen($matches[0]);
        $token = new TagToken(strtolower($matches[0]), true);
        while ($this->consumeAttr($token));
        $ch = $this->raw[$this->at] ?? '';
        if ($ch === '/') {
            $this->at += 2; // consume "/>"
            $token->isSelfClosing = true;
            $this->emit($token);
        } elseif ($ch === '>') {
            $this->at++;
            $this->emit($token);
        } else { // EOF encountered
            $this->emit(new EofToken());
        }
    }
}
