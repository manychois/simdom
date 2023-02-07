<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use PHPUnit\Framework\TestCase;
use Traversable;

final class TestUtility
{
    public static function assertCount(int $expected, array|Traversable $t): void
    {
        $count = is_array($t) ? count($t) : iterator_count($t);
        TestCase::assertEquals($expected, $count);
    }
}
