<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;

class StringStream
{
    const THRESHOLD = 1024;

    private string $buffer;
    private int $pos;
    private int $len;

    public function __construct(string $src)
    {
        $this->buffer = $src;
        $this->len = strlen($src);
        $this->pos = 0;
    }

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

    public function current(): string
    {
        return $this->buffer[$this->pos] ?? '';
    }

    public function findNextStr(string $str): int
    {
        $pos = strpos($this->buffer, $str, $this->pos);
        if ($pos === false) {
            return -1;
        }

        return $pos;
    }

    public function hasNext(): bool
    {
        return $this->pos < $this->len;
    }

    public function peek(int $length): string
    {
        return substr($this->buffer, $this->pos, $length);
    }

    public function readTo(int $end): string
    {
        if ($end <= $this->pos) {
            return '';
        }

        $str = substr($this->buffer, $this->pos, $end - $this->pos);
        $this->pos = $end;
        $this->reduceUsage();

        return $str;
    }

    public function readToEnd(): string
    {
        $str = substr($this->buffer, $this->pos);
        $this->buffer = '';
        $this->pos = 0;
        $this->len = 0;

        return $str;
    }

    public function regexMatch(string $regex): MatchResult
    {
        return MatchResult::test($this->buffer, $regex, $this->pos);
    }

    public function rollback(): void
    {
        if ($this->pos > 0) {
            --$this->pos;
        }
    }

    private function reduceUsage(): void
    {
        if ($this->pos >= self::THRESHOLD) {
            $this->buffer = substr($this->buffer, $this->pos);
            $this->pos = 0;
            $this->len = strlen($this->buffer);
        }
    }
}
