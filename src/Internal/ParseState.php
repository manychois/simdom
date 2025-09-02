<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\AbstractParentNode;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Doctype;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Text;

/**
 * Represents the state of the HTML parser.
 */
final class ParseState
{
    private const string WHITESPACE = "\t\n\f ";
    public readonly AbstractParentNode $context;
    private string $source;
    private AbstractParentNode $currentParent;

    /**
     * Initializes a new instance of the ParseState class.
     *
     * @param string             $source  the HTML source code to be parsed
     * @param AbstractParentNode $context the context node for parsing
     */
    public function __construct(string $source, AbstractParentNode $context)
    {
        $source = preg_replace('/\r\n?/', "\n", $source);
        assert(is_string($source));
        $source = preg_replace('/[\x00-\x08\x0e-\x1f\x7f]/', "\u{FFFD}", $source);
        assert(is_string($source));
        $this->source = $source;
        $this->context = $context;
        $this->currentParent = $context;
    }

    /**
     * Parses the HTML source code.
     */
    public function parse(): void
    {
        while ('' !== $this->source) {
            $ch0 = $this->source[0];
            if ('<' === $ch0) {
                $ch1 = $this->source[1] ?? '';
                if ('' === $ch1) {
                    $this->source = '';
                    $text = Text::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create('<');
                    $this->appendText($text);
                    continue;
                }

                if ('!' === $ch1) {
                    $comment = $this->parseComment();
                    if (null !== $comment) {
                        $this->currentParent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($comment);
                        continue;
                    }
                    $doctype = $this->parseDoctype();
                    if (null !== $doctype) {
                        $this->currentParent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($doctype);
                        continue;
                    }
                    $this->source = ltrim(substr($this->source, 2), self::WHITESPACE); // skip "<!"
                    $comment = $this->parseBogusComment();
                    $this->currentParent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($comment);
                    continue;
                }

                if ('/' === $ch1) {
                    $this->source = substr($this->source, 2); // skip "</"
                    $this->parseEndTag();
                    continue;
                }

                $isSelfClosing = false;
                $element = $this->parseOpenTag($isSelfClosing);
                if (null === $element) {
                    // @phpstan-ignore notIdentical.alwaysTrue
                    if ('' !== $this->source) {
                        $this->source = \substr($this->source, 1); // skip "<"
                        $comment = $this->parseBogusComment();
                        $this->currentParent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($comment);
                    }
                } else {
                    $this->currentParent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($element);
                    if (!$isSelfClosing && !$element->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsVoid) {
                        $this->currentParent = $element;
                    }
                }
                continue;
            }

            $text = $this->parseText();
            $this->appendText($text);
        }
    }

    private function appendText(Text $text): void
    {
        $lastNode = $this->currentParent->childNodes->at(-1);
        if ($lastNode instanceof Text) {
            $lastNode->data .= $text->data;
        } else {
            $parent = $this->currentParent;
            if ($parent instanceof Document && ctype_space($text->data)) {
                return;
            }

            if ($parent instanceof Element) {
                if ('html' === $parent->name && ctype_space($text->data)) {
                    return;
                }

                if (
                    in_array($parent->name, ['listing', 'pre', 'textarea'], true)
                    && 0 === $parent->childNodes->count()
                    && ($text->data[0] ?? '') === "\n"
                ) {
                    $text->data = substr($text->data, 1); // remove leading newline
                }
            }

            if ('' !== $text->data) {
                $parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($text);
            }
        }
    }

    private function parseComment(): ?Comment
    {
        $matched = preg_match('/^<!---?>/s', $this->source, $matches);
        if (1 === $matched) {
            $this->source = substr($this->source, strlen($matches[0]));

            return Comment::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create('');
        }

        $matched = preg_match('/^<!--(([^-]|-(?!->))*)-->/s', $this->source, $matches);
        if (1 !== $matched) {
            return null;
        }

        $data = $matches[1];
        $comment = Comment::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create($data);
        $this->source = substr($this->source, strlen($matches[0]));

        return $comment;
    }

    private function parseDoctype(): ?Doctype
    {
        $matched = preg_match('/^<!DOCTYPE/i', $this->source, $matches);
        if (1 !== $matched) {
            return null;
        }

        $name = 'html';
        $publicId = '';
        $systemId = '';
        $this->source = substr($this->source, 9); // skip "<!DOCTYPE"
        $pos = strpos($this->source, '>');
        if (false === $pos) {
            $s = $this->source;
            $this->source = '';
        } else {
            $s = substr($this->source, 0, $pos);
            $this->source = substr($this->source, $pos + 1); // after ">"
        }
        $matched = preg_match('/^\s*([^>\s]+)\s*/s', $s, $matches);
        if (1 !== $matched) {
            return Doctype::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create($name, $publicId, $systemId);
        }

        $name = $matches[1];
        $s = substr($s, strlen($matches[0]));
        $keyword = strtoupper(substr($s, 0, 6));
        if ('PUBLIC' === $keyword) {
            $s = ltrim(substr($s, 6), self::WHITESPACE);
            $matched = preg_match('/^\'([^\']*)\'|"([^"]*)"/s', $s, $matches);
            if (1 === $matched) {
                $publicId = $matches[2] ?? $matches[1];
                $s = ltrim(substr($s, strlen($matches[0])), self::WHITESPACE);
                $matched = preg_match('/^\'([^\']*)\'|"([^"]*)"/s', $s, $matches);
                if (1 === $matched) {
                    $systemId = $matches[2] ?? $matches[1];
                }
            }
        } elseif ('SYSTEM' === $keyword) {
            $s = substr($s, 6);
            $s = ltrim($s, self::WHITESPACE);
            $matched = preg_match('/^\'([^\']*)\'|"([^"]*)"/s', $s, $matches);
            if (1 === $matched) {
                $systemId = $matches[2] ?? $matches[1];
            }
        }

        return Doctype::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create($name, $publicId, $systemId);
    }

    private function parseEndTag(): void
    {
        $ch0 = $this->source[0] ?? '';
        if ('' === $ch0) {
            $text = Text::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create('</');
            $this->appendText($text);

            return;
        }

        if ('>' === $ch0) {
            $this->source = substr($this->source, 1); // skip ">"

            return;
        }

        $matched = preg_match('/^([a-z][^\/>\t\n\f ]*)[\t\n\f ]*/i', $this->source, $matches);
        if (1 !== $matched) {
            $comment = $this->parseBogusComment();
            $this->currentParent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Append($comment);

            return;
        }

        $tagName = strtolower($matches[1]);
        $this->source = substr($this->source, strlen($matches[0]));

        $isTagClosed = false;
        while (true) {
            $this->parseAttr(null);
            $ch0 = $this->source[0] ?? '';
            if ('/' === $ch0) {
                $ch1 = $this->source[1] ?? '';
                if ('>' === $ch1) {
                    $this->source = substr($this->source, 2); // skip "/>"
                    $isTagClosed = true;
                    break;
                }
                $this->source = ltrim(substr($this->source, 1), self::WHITESPACE); // skip "/" and whitespace after it
            } elseif ('>' === $ch0) {
                $this->source = substr($this->source, 1); // skip ">"
                $isTagClosed = true;
                break;
            } elseif ('' === $ch0) {
                break;
            }
        }

        if ($isTagClosed) {
            $matchedElement = $this->currentParent->closestFn(static fn (Element $e) => $e->name === $tagName);
            if (null !== $matchedElement) {
                if ($matchedElement->contains($this->context)) {
                    $this->currentParent = $this->context;
                } else {
                    assert(null !== $matchedElement->parent);
                    $this->currentParent = $matchedElement->parent;
                }
            }
        }
    }

    private function parseBogusComment(): Comment
    {
        $pos = strpos($this->source, '>');
        if (false === $pos) {
            $data = $this->source;
            $this->source = '';
        } else {
            $data = substr($this->source, 0, $pos);
            $this->source = substr($this->source, $pos + 1); // after ">"
        }

        return Comment::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create($data);
    }

    private function parseOpenTag(bool &$isSelfClosing): ?Element
    {
        $isSelfClosing = false;
        $matched = preg_match('/^<([a-z][^\/>\s]*)/i', $this->source, $matches);
        if (1 !== $matched) {
            return null;
        }

        $element = Element::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create(strtolower($matches[1]));
        $this->source = ltrim(substr($this->source, strlen($matches[0])), self::WHITESPACE);

        $isTagClosed = false;
        while (true) {
            $this->parseAttr($element);
            $ch0 = $this->source[0] ?? '';
            if ('/' === $ch0) {
                $ch1 = $this->source[1] ?? '';
                if ('>' === $ch1) {
                    $this->source = substr($this->source, 2); // skip "/>"
                    $isSelfClosing = true;
                    $isTagClosed = true;
                    break;
                }
                $this->source = ltrim(substr($this->source, 1), self::WHITESPACE); // skip "/" and whitespace after it
            } elseif ('>' === $ch0) {
                $this->source = substr($this->source, 1); // skip ">"
                $isTagClosed = true;
                break;
            } elseif ('' === $ch0) {
                break;
            }
        }

        if (!$isTagClosed) {
            return null;
        }

        return $element;
    }

    private function parseAttr(?Element $element): void
    {
        $matched = preg_match('/^(=?[^\/>=\t\n\f ]+)[\t\n\f ]*/', $this->source, $matches);
        if (1 !== $matched) {
            return;
        }
        $name = strtolower($matches[1]);
        $this->source = substr($this->source, strlen($matches[0]));
        $value = '';
        $ch0 = $this->source[0] ?? '';
        if ('=' !== $ch0) {
            $element?->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetAttr($name, $value);

            return;
        }

        $this->source = ltrim(substr($this->source, 1), self::WHITESPACE); // skip '='
        $ch0 = $this->source[0] ?? '';
        if ('"' === $ch0 || '\'' === $ch0) {
            $pos = strpos($this->source, $ch0, 1);
            if (false === $pos) {
                if (null !== $element) {
                    $value = $this->unescape(substr($this->source, 1));
                }
                $this->source = '';
            } else {
                if (null !== $element) {
                    $value = $this->unescape(substr($this->source, 1, $pos - 1));
                }
                $this->source = ltrim(substr($this->source, $pos + 1), self::WHITESPACE);
            }
        } else {
            $matched = \preg_match('/^([^\/>\t\n\f ]+)[\t\n\f ]*/', $this->source, $matches);
            if (1 === $matched) {
                if (null !== $element) {
                    $value = $this->unescape($matches[1]);
                }
                $this->source = substr($this->source, strlen($matches[0]));
            }
        }

        $element?->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetAttr($name, $value);
    }

    private function parseText(): Text
    {
        $pos = false;
        $doUnescape = true;
        if ($this->currentParent instanceof Element) {
            if ($this->currentParent->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRawtext) {
                $doUnescape = false;
                if ('plaintext' !== $this->currentParent->name) {
                    $pos = strpos($this->source, '</' . $this->currentParent->name);
                }
            } elseif ($this->currentParent->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRcdata) {
                $pos = strpos($this->source, '</' . $this->currentParent->name);
            } else {
                $pos = strpos($this->source, '<');
            }
        } else {
            $pos = strpos($this->source, '<');
        }
        $pos = false === $pos ? null : $pos;

        $data = substr($this->source, 0, $pos);
        $data = $doUnescape ? $this->unescape($data) : $data;
        $this->source = null === $pos ? '' : substr($this->source, $pos);

        return Text::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create($data);
    }

    private function unescape(string $s): string
    {
        return html_entity_decode($s, \ENT_HTML5 | \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
