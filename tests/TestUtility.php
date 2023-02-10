<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use PHPUnit\Framework\TestCase;

final class TestUtility
{
    /**
     * @param int $expected Expected count
     * @param array<mixed>|\Traversable<mixed> $t Array or Traversable to count
     */
    public static function assertCount(int $expected, $t): void
    {
        $count = is_array($t) ? count($t) : iterator_count($t);
        TestCase::assertEquals($expected, $count);
    }
}
