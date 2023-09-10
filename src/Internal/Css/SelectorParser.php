<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use IntlChar;
use InvalidArgumentException;
use LogicException;

/**
 * The CSS selector.
 */
class SelectorParser
{
    private const ESC_REGEX = '\\\\[0-9a-fA-F]{1,6} ?|\\\\.';
    private const CHAR_REGEX = '[a-zA-Z_]|[^\x00-\x7F]' . '|' . self::ESC_REGEX;

    private string $raw = '';
    private int $at = 0;
    private int $len = 0;

    /**
     * Parses a CSS selector.
     *
     * @param string $selector The CSS selector to parse.
     *
     * @return AbstractSelector The parsed selector.
     */
    public function parse(string $selector): AbstractSelector
    {
        $this->raw = $selector;
        $this->at = 0;
        $this->len = strlen($selector);

        $or = new OrSelector();
        $this->consumeWhitespace();
        $regex = '/\s*,?\s*/';
        while ($this->at < $this->len) {
            $cs = $this->parseComplexSelector();
            if ($cs === null) {
                throw new InvalidArgumentException(sprintf('Invalid character found: %s', $this->raw[$this->at]));
            }
            $or->selectors[] = $cs;
            preg_match($regex, $this->raw, $matches, 0, $this->at);
            if ($matches[0] === '') {
                break;
            }
            $this->at += strlen($matches[0]);
        }

        if ($this->at < $this->len) {
            throw new InvalidArgumentException(sprintf('Invalid character found: %s', $this->raw[$this->at]));
        }
        if (count($or->selectors) === 0) {
            throw new InvalidArgumentException(sprintf('Invalid selector: %s', $selector));
        }

        return $or->simplify();
    }

    /**
     * Consumes any whitespace characters.
     */
    protected function consumeWhitespace(): void
    {
        while ($this->at < $this->len) {
            $c = $this->raw[$this->at];
            if ($c !== ' ' && $c !== "\t") {
                break;
            }

            $this->at++;
        }
    }

    #region Parse selectors

    /**
     * Parses an attribute selector.
     *
     * @return null|AttributeSelector The parsed attribute selector, or null if not found.
     */
    protected function parseAttributeSelector(): ?AttributeSelector
    {
        $c = $this->raw[$this->at] ?? '';
        assert($c === '[');

        $this->at++;
        $this->consumeWhitespace();
        $name = $this->consumeIdentToken();

        if ($name === '') {
            throw new InvalidArgumentException('Invalid attribute selector found');
        }

        $this->consumeWhitespace();

        $matcher = AttrMatcher::Exists;
        $pattern = '/[~|^$*]?=/';
        $isMatch = preg_match($pattern, $this->raw, $matches, PREG_OFFSET_CAPTURE, $this->at);
        if ($isMatch === 1) {
            if ($matches[0][1] === $this->at) {
                $capture = $matches[0][0];
                $matcher = match ($capture) {
                    '=' => AttrMatcher::Equals,
                    '~=' => AttrMatcher::Includes,
                    '|=' => AttrMatcher::DashMatch,
                    '^=' => AttrMatcher::PrefixMatch,
                    '$=' => AttrMatcher::SuffixMatch,
                    '*=' => AttrMatcher::SubstringMatch,
                    default => throw new InvalidArgumentException('Invalid attribute matcher found'),
                };
                $this->at += strlen($capture);
            }
        }
        $this->consumeWhitespace();

        $c = $this->raw[$this->at] ?? '';
        if ($c === '') {
            throw new InvalidArgumentException('Invalid attribute selector found');
        }
        if ($c === ']') {
            if ($matcher === AttrMatcher::Exists) {
                $this->at++;

                return new AttributeSelector($name, $matcher, '', true);
            }
            throw new InvalidArgumentException('Attribute selector value is missing');
        }

        $value = $this->consumeStringToken();
        if ($value === null) {
            $value = $this->consumeIdentToken();
            if ($value === '') {
                throw new InvalidArgumentException('Invalid attribute selector value found');
            }
        }

        $pattern = '/\s*([isIS]?)\s*\\]/';
        $isMatch = preg_match($pattern, $this->raw, $matches, PREG_OFFSET_CAPTURE, $this->at);
        if ($isMatch === 1) {
            if ($matches[0][1] === $this->at) {
                $capture = $matches[0][0];
                $caseSensitive = $matches[1][0];
                $caseSensitive = $caseSensitive !== 'i' && $caseSensitive !== 'I';
                $this->at += strlen($capture);

                return new AttributeSelector($name, $matcher, $value, $caseSensitive);
            }
        }

        throw new InvalidArgumentException('Invalid attribute selector found');
    }

    /**
     * Parses a class selector.
     *
     * @return ClassSelector The parsed class selector.
     */
    protected function parseClassSelector(): ClassSelector
    {
        $c = $this->raw[$this->at] ?? '';
        assert($c === '.');
        $this->at++;
        $ident = $this->consumeIdentToken();
        if ($ident === '') {
            throw new InvalidArgumentException('Invalid class selector found');
        }

        return new ClassSelector($ident);
    }

    /**
     * Parses a complex selector.
     *
     * @return null|ComplexSelector The parsed complex selector, or null if not found.
     */
    protected function parseComplexSelector(): ?ComplexSelector
    {
        $compound = $this->parseCompoundSelector();
        if ($compound === null) {
            return null;
        }

        $complex = new ComplexSelector($compound);

        $pattern = '/\s*([>+~]|\\|\\|)?\s*/';
        while ($this->at < $this->len) {
            preg_match($pattern, $this->raw, $matches, 0, $this->at);
            if ($matches[0] === '') {
                break;
            }

            $combinator = match ($matches[1] ?? ' ') {
                ' ' => Combinator::Descendant,
                '>' => Combinator::Child,
                '+' => Combinator::AdjacentSibling,
                '~' => Combinator::GeneralSibling,
                '||' => throw new InvalidArgumentException('Column combinator is not supported'),
                default => throw new LogicException('Invalid combinator found'),
            };
            $this->at += strlen($matches[0]);

            $compound = $this->parseCompoundSelector();
            if ($compound === null) {
                if ($combinator === Combinator::Descendant) {
                    // it is a whitespace not a combinator
                    break;
                }
                throw new InvalidArgumentException(
                    sprintf('Missing complex selector after combinator "%s"', $combinator->value),
                );
            }
            $complex->combinators[] = $combinator;
            $complex->selectors[] = $compound;
        }

        return $complex;
    }

    /**
     * Parses a compound selector.
     *
     * @return null|CompoundSelector The parsed compound selector, or null if not found.
     */
    protected function parseCompoundSelector(): ?CompoundSelector
    {
        $oldAt = $this->at;
        $compound = new CompoundSelector();
        $compound->type = $this->parseTypeSelector();

        while ($this->at < $this->len) {
            $oldAt = $this->at;
            $this->consumeWhitespace();
            $subclass = $this->parseSubclassSelector();
            if ($subclass === null) {
                // undo the whitespace consumption, as this could be descendant combinator ( ).
                $this->at = $oldAt;
                break;
            }
            $compound->selectors[] = $subclass;
        }

        if ($compound->type === null && count($compound->selectors) === 0) {
            $this->at = $oldAt;

            return null;
        }

        return $compound;
    }

    /**
     * Parses an ID selector.
     *
     * @return IdSelector The parsed ID selector.
     */
    protected function parseIdSelector(): IdSelector
    {
        $pattern = '/#(' . self::CHAR_REGEX . '|[0-9-]' . ')*/';
        $isMatch = preg_match($pattern, $this->raw, $matches, PREG_OFFSET_CAPTURE, $this->at);
        assert($isMatch === 1 && $matches[0][1] === $this->at);

        $capture = $matches[0][0];
        $id = $this->unescape(substr($capture, 1));
        if ($id === '') {
            throw new InvalidArgumentException('Invalid ID selector found');
        }
        $this->at += strlen($capture);

        return new IdSelector($id);
    }

    /**
     * Parses a subclass selector.
     *
     * @return IdSelector|ClassSelector|AttributeSelector|null The parsed subclass selector, or null if not found.
     */
    protected function parseSubclassSelector(): IdSelector|ClassSelector|AttributeSelector|null
    {
        $c = $this->raw[$this->at] ?? '';
        switch ($c) {
            case '#':
                return $this->parseIdSelector();
            case '.':
                return $this->parseClassSelector();
            case '[':
                return $this->parseAttributeSelector();
            default:
                return null;
        }
    }

    /**
     * Parses a type selector.
     *
     * @return null|TypeSelector The parsed type selector, or null if not found.
     */
    protected function parseTypeSelector(): ?TypeSelector
    {
        $c = $this->raw[$this->at] ?? '';
        if ($c === '*') {
            $this->at++;

            return new TypeSelector('*');
        }

        $ident = $this->consumeIdentToken();
        if ($ident !== '') {
            return new TypeSelector($ident);
        }

        return null;
    }

    #endregion

    /**
     * Unescapes the given text.
     *
     * @param string $text The text to unescape.
     *
     * @return string The unescaped text.
     */
    protected function unescape(string $text): string
    {
        $result = preg_replace_callback('/\\\\(?<hex>[0-9a-fA-F]{1,6}) ?|\\\\(?<esc>.)/', static function ($matches) {
            if (isset($matches['esc'])) {
                return $matches['esc'];
            }

            $codePoint = hexdec($matches['hex']);
            if ($codePoint > 0x10FFFF) {
                throw new InvalidArgumentException(sprintf('Invalid code point %s found', $matches['hex']));
            }
            assert(is_int($codePoint));

            return IntlChar::chr($codePoint);
        }, $text);

        assert(is_string($result));

        return $result;
    }

    /**
     * Consumes an identifier token.
     *
     * @return string The identifier token, or an empty string if not found.
     */
    protected function consumeIdentToken(): string
    {
        $pattern = '(?<first>(-?(' . self::CHAR_REGEX . ')|--)?)';
        $pattern = '/' . $pattern . '(' . self::CHAR_REGEX . '|[0-9]|-)*/';
        preg_match($pattern, $this->raw, $matches, PREG_OFFSET_CAPTURE, $this->at);
        if ($matches[0][1] === $this->at) {
            $capture = $matches[0][0];
            if ($capture !== '') {
                $ident = ($matches['first'][0] === '' ? '--' : '') . $capture;
                $this->at += strlen($capture);

                return $this->unescape($ident);
            }
        }

        return '';
    }

    /**
     * Consumes a string token.
     *
     * @return null|string The string token, or null if not found.
     */
    protected function consumeStringToken(): ?string
    {
        $c = $this->raw[$this->at] ?? '';
        if ($c !== '"' && $c !== "'") {
            return null;
        }

        $pattern = $c === '"'
            ? '/"([^"\\\\]+|\\\\.)+"/'
            : "/'([^'\\\\]+|\\\\.)+'/";
        $isMatch = preg_match($pattern, $this->raw, $matches, PREG_OFFSET_CAPTURE, $this->at);
        if ($isMatch === 1) {
            if ($matches[0][1] === $this->at) {
                $capture = $matches[0][0];
                $this->at += strlen($capture);

                return $this->unescape(substr($capture, 1, -1));
            }
        }

        return null;
    }
}
