<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

class MatchResult
{
    public readonly bool $success;
    public readonly string $value;
    /**
     * @var array<int, string>
     */
    public readonly array $captures;

    public function __construct(bool $success, string $value, array $captures)
    {
        $this->success = $success;
        $this->value = $value;
        $this->captures = $captures;
    }

    public static function test(string $subject, string $pattern, int $offset = 0): self
    {
        $success = preg_match($pattern, $subject, $matches, 0, $offset) === 1;
        $value = '';
        $captures = [];
        if ($success) {
            $captures = $matches;
            $value = array_shift($captures);
        }

        return new self($success, $value, $captures);
    }
}
