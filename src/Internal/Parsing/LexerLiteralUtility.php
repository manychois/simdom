<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Provides methods for handling string literals for the lexer.
 */
class LexerLiteralUtility
{
    /**
     * Converts HTML entities to their corresponding UTF-8 characters.
     *
     * @param string $str The input string.
     *
     * @return string The decoded string.
     */
    public static function decodeHtml(string $str): string
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
    public static function fixNull(string $str): string
    {
        return str_replace("\0", "\u{FFFD}", $str);
    }
}
