<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;

/**
 * Represents a string stream for parsing purposes.
 */
class StringStream
{
    private const THRESHOLD = 1024;

    private int $pos = 0;
    private string $buffer;
    private int $len;

    /**
     * Constructor.
     *
     * @param string $src The source string.
     */
    public function __construct(string $src)
    {
        $this->buffer = $src;
        $this->len = strlen($src);
    }

    /**
     * Moves the current position for the specified step.
     *
     * @param int $step The number of characters to move.
     */
    public function advance(int $step = 1): void
    {
        if ($step < 0) {
            throw new InvalidArgumentException('Step cannot be negative.');
        }

        if ($step === 0 || $this->pos === $this->len) {
            return;
        }

        $this->pos += $step;
        if ($this->pos > $this->len) {
            $this->pos = $this->len;
        }
        $this->reduceUsage();
    }

    /**
     * Gets the character at the current position.
     *
     * @return string The character at the current position, or an empty string if the current position is at the end of
     * the stream.
     */
    public function current(): string
    {
        return $this->buffer[$this->pos] ?? '';
    }

    /**
     * Finds the next occurrence of the specified character.
     *
     * @param string $str The text to find.
     *
     * @return int The position of the next occurrence of the specified character, or -1 if not found.
     */
    public function findNextStr(string $str): int
    {
        $pos = strpos($this->buffer, $str, $this->pos);
        if ($pos === false) {
            return -1;
        }

        return $pos - $this->pos;
    }

    /**
     * Checks if the stream has more characters to read.
     *
     * @return bool True if the stream has more characters to read, false otherwise.
     */
    public function hasNext(): bool
    {
        return $this->pos < $this->len;
    }

    /**
     * Gets the substring from the current position without moving the current position.
     *
     * @param int $length The length of the substring.
     *
     * @return string The substring from the current position.
     */
    public function peek(int $length): string
    {
        return substr($this->buffer, $this->pos, $length);
    }

    /**
     * Prepends the specified text to the stream.
     *
     * @param string $text The text to prepend.
     */
    public function prepend(string $text): void
    {
        $this->buffer = $text . substr($this->buffer, $this->pos);
        $this->pos = 0;
        $this->len = strlen($this->buffer);
    }

    /**
     * Reads the specified number of characters from the stream.
     *
     * @param int $length The number of characters to read.
     *
     * @return string The characters read.
     */
    public function read(int $length): string
    {
        if ($length < 1) {
            return '';
        }

        $str = substr($this->buffer, $this->pos, $length);
        $this->pos += $length;
        $this->reduceUsage();

        return $str;
    }

    /**
     * Reads the remaining characters from the stream.
     *
     * @return string The remaining characters from the stream.
     */
    public function readToEnd(): string
    {
        $str = substr($this->buffer, $this->pos);
        $this->buffer = '';
        $this->pos = 0;
        $this->len = 0;

        return $str;
    }

    /**
     * Matches the specified regular expression at the current position.
     * Avoid the use of "^" and "$" in the regular expression as the stream may not contain the full source string.
     *
     * @param non-empty-string $regex The regular expression to match.
     *
     * @return MatchResult The result of the match.
     */
    public function regexMatch(string $regex): MatchResult
    {
        return MatchResult::test($this->buffer, $regex, $this->pos);
    }

    /**
     * Shortens the buffer if the current position is greater than or equal to the threshold.
     */
    private function reduceUsage(): void
    {
        if ($this->pos >= self::THRESHOLD) {
            $this->buffer = substr($this->buffer, $this->pos);
            $this->pos = 0;
            $this->len = strlen($this->buffer);
        }
    }
}
