<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\StringStream;

/**
 * Represents an attribute selector.
 */
class AttributeSelector extends AbstractSubclassSelector
{
    /**
     * The attribute name to match.
     *
     * @var string
     */
    public readonly string $name;

    /**
     * The attribute matcher, i.e. how to compare the attribute value.
     *
     * @var AttrMatcher
     */
    public readonly AttrMatcher $matcher;

    /**
     * The attribute value to match.
     */
    public readonly string $value;

    /**
     * Whether the attribute value comparison is case sensitive.
     *
     * @var bool
     */
    public readonly bool $caseSensitive;

    /**
     * Creates a new AttributeSelector instance.
     *
     * @param string      $name          The attribute name to match.
     * @param AttrMatcher $matcher       The attribute matcher, i.e. how to compare the attribute value.
     * @param string      $value         The attribute value to match.
     * @param bool        $caseSensitive Whether the attribute value comparison is case sensitive.
     */
    public function __construct(string $name, AttrMatcher $matcher, string $value, bool $caseSensitive)
    {
        $this->name = mb_strtolower($name);
        $this->matcher = $matcher;
        $this->value = $value;
        $this->caseSensitive = $caseSensitive;
    }

        /**
     * Parses an attribute selector.
         *
         * @param StringStream $str The string stream to parse.
         *
         * @return null|self The parsed attribute selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $chr = $str->current();
        assert($chr === '[');

        $str->advance();
        SelectorParser::consumeWhitespace($str);
        $name = SelectorParser::consumeIdentToken($str);

        if ($name === '') {
            throw new InvalidArgumentException('Invalid attribute selector found');
        }

        SelectorParser::consumeWhitespace($str);

        $matcher = self::parseAttrMatcher($str);
        SelectorParser::consumeWhitespace($str);

        $chr = $str->current();
        if ($chr === '') {
            throw new InvalidArgumentException('Invalid attribute selector found');
        }
        if ($chr === ']') {
            if ($matcher === AttrMatcher::Exists) {
                $str->advance();

                return new AttributeSelector($name, $matcher, '', true);
            }
            throw new InvalidArgumentException('Attribute selector value is missing');
        }

        $value = SelectorParser::consumeStringToken($str);
        if ($value === null) {
            $value = SelectorParser::consumeIdentToken($str);
            if ($value === '') {
                throw new InvalidArgumentException('Invalid attribute selector value found');
            }
        }

        $matchResult = $str->regexMatch('/\s*([isIS]?)\s*\\]/');
        if ($matchResult->success && $matchResult->value === $str->peek(strlen($matchResult->value))) {
            $caseSensitive = strcasecmp($matchResult->captures[0], 'i') !== 0;
            $str->advance(strlen($matchResult->value));

            return new AttributeSelector($name, $matcher, $value, $caseSensitive);
        }

        throw new InvalidArgumentException('Invalid attribute selector found');
    }

    #region extends AbstractSubclassSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $str = '[' . $this->name;
        if ($this->matcher === AttrMatcher::Exists) {
            return $str . ']';
        }

        $str .= $this->matcher->value;

        $value = preg_replace_callback('/[\\\\"\\n]/', static fn ($matches) => match ($matches[0]) {
            "\n" => '\\a ',
            '"' => '\\"',
            default => '\\\\',
        }, $this->value);
        $str .= '"' . $value . '"';

        if (!$this->caseSensitive) {
            $str .= ' i';
        }
        $str .= ']';

        return $str;
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        if (!$element->hasAttribute($this->name)) {
            return false;
        }

        $attrValue = $element->getAttribute($this->name) ?? '';
        $pregQuote = preg_quote($this->value, '/');
        $pattern = match ($this->matcher) {
            AttrMatcher::Exists => '',
            AttrMatcher::Equals => '/^' . $pregQuote . '$/u',
            AttrMatcher::Includes => '/(^|\\s)' . $pregQuote . '($|\\s)/u',
            AttrMatcher::DashMatch => '/^' . $pregQuote . '(-|$)/u',
            AttrMatcher::PrefixMatch => '/^' . $pregQuote . '/u',
            AttrMatcher::SuffixMatch => '/' . $pregQuote . '$/u',
            AttrMatcher::SubstringMatch => '/' . $pregQuote . '/u',
        };
        if ($pattern !== '' && !$this->caseSensitive) {
            $pattern .= 'i';
        }

        return $pattern === '' || preg_match($pattern, $attrValue) === 1;
    }

    #endregion

    /**
     * Parses an attribute matcher.
     *
     * @param StringStream $str The string stream to consume the token from.
     *
     * @return AttrMatcher The parsed attribute matcher.
     */
    private static function parseAttrMatcher(StringStream $str): AttrMatcher
    {
        $matcher = AttrMatcher::Exists;
        $matchResult = $str->regexMatch('/[~|^$*]?=/');
        if ($matchResult->success) {
            $len = strlen($matchResult->value);
            if ($str->peek($len) === $matchResult->value) {
                $matcher = match ($matchResult->value) {
                    '=' => AttrMatcher::Equals,
                    '~=' => AttrMatcher::Includes,
                    '|=' => AttrMatcher::DashMatch,
                    '^=' => AttrMatcher::PrefixMatch,
                    '$=' => AttrMatcher::SuffixMatch,
                    '*=' => AttrMatcher::SubstringMatch,
                    default => throw new InvalidArgumentException('Invalid attribute matcher found'),
                };
                $str->advance($len);
            }
        }

        return $matcher;
    }
}
