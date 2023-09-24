<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

/**
 * Represents the result of a regular expression match.
 */
class MatchResult
{
    public readonly bool $success;
    public readonly string $value;
    /**
     * @var array<int|string, string>
     */
    public readonly array $captures;

    /**
     * Creates a new instance of MatchResult.
     *
     * @param bool                      $success  If the match is successful.
     * @param string                    $value    The matched value.
     * @param array<int|string, string> $captures The captured groups.
     */
    public function __construct(bool $success, string $value, array $captures)
    {
        $this->success = $success;
        $this->value = $value;
        $this->captures = $captures;
    }

    /**
     * Tests if the given subject matches the given pattern.
     *
     * @param string           $subject The subject to test.
     * @param non-empty-string $pattern The pattern to match.
     * @param int              $offset  The offset to start matching.
     *
     * @return MatchResult The result of the match.
     */
    public static function test(string $subject, string $pattern, int $offset = 0): self
    {
        $success = preg_match($pattern, $subject, $matches, 0, $offset) === 1;
        $value = '';
        $captures = [];
        if ($success) {
            $captures = $matches;
            $value = array_shift($captures) ?? '';
        }

        return new self($success, $value, $captures);
    }
}
