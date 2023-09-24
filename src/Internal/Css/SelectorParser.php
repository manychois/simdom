<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use IntlChar;
use InvalidArgumentException;
use Manychois\Simdom\Internal\StringStream;

/**
 * The CSS selector.
 */
class SelectorParser
{
    public const ESC_REGEX = '\\\\[0-9a-fA-F]{1,6} ?|\\\\.';
    public const CHAR_REGEX = '[a-zA-Z_]|[^\x00-\x7F]' . '|' . self::ESC_REGEX;

    private StringStream $str;

    /**
     * Creates a new instance of the SelectorParser class.
     */
    public function __construct()
    {
        $this->str = new StringStream('');
    }

    /**
     * Consumes an identifier token.
     *
     * @param StringStream $str The string stream to consume the token from.
     *
     * @return string The identifier token, or an empty string if not found.
     */
    public static function consumeIdentToken(StringStream $str): string
    {
        $pattern = '(?<first>(-?(' . self::CHAR_REGEX . ')|--)?)';
        $pattern = '/' . $pattern . '(' . self::CHAR_REGEX . '|[0-9]|-)*/';
        $matchResult = $str->regexMatch($pattern);

        if ($matchResult->value === '') {
            return '';
        }

        $len = strlen($matchResult->value);
        if ($str->peek($len) !== $matchResult->value) {
            return '';
        }

        $ident = ($matchResult->captures['first'] === '' ? '--' : '') . $matchResult->value;
        $str->advance($len);

        return static::unescape($ident);
    }

    /**
     * Consumes a string token.
     *
     * @param StringStream $str The string stream to consume the token from.
     *
     * @return null|string The string token, or null if not found.
     */
    public static function consumeStringToken(StringStream $str): ?string
    {
        $chr = $str->current();
        if ($chr !== '"' && $chr !== "'") {
            return null;
        }

        $pattern = $chr === '"'
            ? '/"([^"\\\\]+|\\\\.)+"/'
            : "/'([^'\\\\]+|\\\\.)+'/";
        $matchResult = $str->regexMatch($pattern);
        if ($matchResult->success) {
            $len = strlen($matchResult->value);
            if ($str->peek($len) === $matchResult->value) {
                $str->advance($len);

                return static::unescape(substr($matchResult->value, 1, -1));
            }
        }

        return null;
    }

    /**
     * Consumes any whitespace characters.
     *
     * @param StringStream $str The string stream to consume the token from.
     *
     * @return string The consumed whitespace characters.
     */
    public static function consumeWhitespace(StringStream $str): string
    {
        $whitespace = '';
        while ($str->hasNext()) {
            $chr = $str->current();
            if ($chr !== ' ' && $chr !== "\t") {
                break;
            }
            $whitespace .= $chr;
            $str->advance();
        }

        return $whitespace;
    }

    /**
     * Unescapes the given text.
     *
     * @param string $text The text to unescape.
     *
     * @return string The unescaped text.
     */
    public static function unescape(string $text): string
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
     * Parses a CSS selector.
     *
     * @param string $selector The CSS selector to parse.
     *
     * @return AbstractSelector The parsed selector.
     */
    public function parse(string $selector): AbstractSelector
    {
        $this->str = new StringStream($selector);

        $orSelector = OrSelector::parse($this->str);
        if ($orSelector === null || count($orSelector->selectors) === 0) {
            throw new InvalidArgumentException(sprintf('Invalid selector: %s', $selector));
        }

        return $orSelector->simplify();
    }
}
