<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use IntlChar;
use InvalidArgumentException;
use LogicException;
use Manychois\Simdom\Internal\StringStream;

/**
 * The CSS selector.
 */
class SelectorParser
{
    private const ESC_REGEX = '\\\\[0-9a-fA-F]{1,6} ?|\\\\.';
    private const CHAR_REGEX = '[a-zA-Z_]|[^\x00-\x7F]' . '|' . self::ESC_REGEX;

    private StringStream $str;

    /**
     * Creates a new instance of the SelectorParser class.
     */
    public function __construct()
    {
        $this->str = new StringStream('');
    }

    /**
     * Parses a CSS selector.
     *
     * @param string $selector The CSS selector to parse.
     *
     * @return AbstractSelector The parsed selector.
     */
    public function parse(string $selector): AbstractSelector
    {
        $this->str = new StringStream($selector);

        $orSelector = new OrSelector();
        $this->consumeWhitespace();
        $regex = '/\s*,?\s*/';
        while ($this->str->hasNext()) {
            $complexSelector = $this->parseComplexSelector();
            if ($complexSelector === null) {
                throw new InvalidArgumentException(sprintf('Invalid character found: %s', $this->str->current()));
            }
            $orSelector->selectors[] = $complexSelector;
            $matchResult = $this->str->regexMatch($regex);
            assert($matchResult->success);
            if ($matchResult->value === '') {
                break;
            }
            $this->str->advance(strlen($matchResult->value));
        }

        if ($this->str->hasNext()) {
            throw new InvalidArgumentException(sprintf('Invalid character found: %s', $this->str->current()));
        }
        if (count($orSelector->selectors) === 0) {
            throw new InvalidArgumentException(sprintf('Invalid selector: %s', $selector));
        }

        return $orSelector->simplify();
    }

    /**
     * Consumes any whitespace characters.
     *
     * @return string The consumed whitespace characters.
     */
    protected function consumeWhitespace(): string
    {
        $ws = '';
        while ($this->str->hasNext()) {
            $chr = $this->str->current();
            if ($chr !== ' ' && $chr !== "\t") {
                break;
            }
            $ws .= $chr;
            $this->str->advance();
        }

        return $ws;
    }

    #region Parse selectors

    /**
     * Parses an attribute matcher.
     *
     * @return AttrMatcher The parsed attribute matcher.
     */
    protected function parseAttrMatcher(): AttrMatcher
    {
        $matcher = AttrMatcher::Exists;
        $matchResult = $this->str->regexMatch('/[~|^$*]?=/');
        if ($matchResult->success) {
            $len = strlen($matchResult->value);
            if ($this->str->peek($len) === $matchResult->value) {
                $matcher = match ($matchResult->value) {
                    '=' => AttrMatcher::Equals,
                    '~=' => AttrMatcher::Includes,
                    '|=' => AttrMatcher::DashMatch,
                    '^=' => AttrMatcher::PrefixMatch,
                    '$=' => AttrMatcher::SuffixMatch,
                    '*=' => AttrMatcher::SubstringMatch,
                    default => throw new InvalidArgumentException('Invalid attribute matcher found'),
                };
                $this->str->advance($len);
            }
        }

        return $matcher;
    }

    /**
     * Parses an attribute selector.
     *
     * @return null|AttributeSelector The parsed attribute selector, or null if not found.
     */
    protected function parseAttributeSelector(): ?AttributeSelector
    {
        $chr = $this->str->current();
        assert($chr === '[');

        $this->str->advance();
        $this->consumeWhitespace();
        $name = $this->consumeIdentToken();

        if ($name === '') {
            throw new InvalidArgumentException('Invalid attribute selector found');
        }

        $this->consumeWhitespace();

        $matcher = $this->parseAttrMatcher();
        $this->consumeWhitespace();

        $chr = $this->str->current();
        if ($chr === '') {
            throw new InvalidArgumentException('Invalid attribute selector found');
        }
        if ($chr === ']') {
            if ($matcher === AttrMatcher::Exists) {
                $this->str->advance();

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

        $matchResult = $this->str->regexMatch('/\s*([isIS]?)\s*\\]/');
        if ($matchResult->success && $matchResult->value === $this->str->peek(strlen($matchResult->value))) {
            $caseSensitive = strcasecmp($matchResult->captures[0], 'i') !== 0;
            $this->str->advance(strlen($matchResult->value));

            return new AttributeSelector($name, $matcher, $value, $caseSensitive);
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
        $chr = $this->str->current();
        assert($chr === '.');
        $this->str->advance();
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

        while ($this->str->hasNext()) {
            $matchResult = $this->str->regexMatch('/\s*([>+~]|\\|\\|)?\s*/');
            if ($matchResult->value === '') {
                break;
            }

            $combinator = match ($matchResult->captures[0] ?? ' ') {
                ' ' => Combinator::Descendant,
                '>' => Combinator::Child,
                '+' => Combinator::AdjacentSibling,
                '~' => Combinator::GeneralSibling,
                '||' => throw new InvalidArgumentException('Column combinator is not supported'),
                default => throw new LogicException('Invalid combinator found'),
            };
            $this->str->advance(strlen($matchResult->value));

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
        $compound = new CompoundSelector();
        $compound->type = $this->parseTypeSelector();

        while ($this->str->hasNext()) {
            $ws = $this->consumeWhitespace();
            $subclass = $this->parseSubclassSelector();
            if ($subclass === null) {
                // undo the whitespace consumption, as this could be descendant combinator ( ).
                $this->str->prepend($ws);
                break;
            }
            $compound->selectors[] = $subclass;
        }

        if ($compound->type === null && count($compound->selectors) === 0) {
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
        $matchResult = $this->str->regexMatch($pattern);
        assert($matchResult->success);
        $len = strlen($matchResult->value);
        assert($this->str->peek($len) === $matchResult->value);
        $id = $this->unescape(substr($matchResult->value, 1));
        if ($id === '') {
            throw new InvalidArgumentException('Invalid ID selector found');
        }
        $this->str->advance($len);

        return new IdSelector($id);
    }

    /**
     * Parses a subclass selector.
     *
     * @return IdSelector|ClassSelector|AttributeSelector|null The parsed subclass selector, or null if not found.
     */
    protected function parseSubclassSelector(): IdSelector|ClassSelector|AttributeSelector|null
    {
        $chr = $this->str->current();
        switch ($chr) {
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
        $chr = $this->str->current();
        if ($chr === '*') {
            $this->str->advance();

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
            /** @var string $str */
            $str = IntlChar::chr($codePoint);

            return $str;
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
        $matchResult = $this->str->regexMatch($pattern);

        if ($matchResult->value === '') {
            return '';
        }

        $len = strlen($matchResult->value);
        if ($this->str->peek($len) !== $matchResult->value) {
            return '';
        }

        $ident = ($matchResult->captures['first'] === '' ? '--' : '') . $matchResult->value;
        $this->str->advance($len);

        return $this->unescape($ident);
    }

    /**
     * Consumes a string token.
     *
     * @return null|string The string token, or null if not found.
     */
    protected function consumeStringToken(): ?string
    {
        $chr = $this->str->current();
        if ($chr !== '"' && $chr !== "'") {
            return null;
        }

        $pattern = $chr === '"'
            ? '/"([^"\\\\]+|\\\\.)+"/'
            : "/'([^'\\\\]+|\\\\.)+'/";
        $matchResult = $this->str->regexMatch($pattern);
        if ($matchResult->success) {
            $len = strlen($matchResult->value);
            if ($this->str->peek($len) === $matchResult->value) {
                $this->str->advance($len);

                return $this->unescape(substr($matchResult->value, 1, -1));
            }
        }

        return null;
    }
}
