<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use Closure;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use PHPUnit\Framework\TestCase;

class ExceptionTester
{
    public function expectPreInsertionException(Closure $fn, PreInsertionException $expected): void
    {
        try {
            $fn();
            TestCase::fail('Expected PreInsertionException');
        } catch (PreInsertionException $pie) {
            TestCase::assertEquals($expected->getMessage(), $pie->getMessage());
            TestCase::assertSame($expected->node, $pie->node);
            TestCase::assertSame($expected->parent, $pie->parent);
            TestCase::assertSame($expected->refChild, $pie->refChild);
        }
    }

    public function expectPreReplaceException(Closure $fn, PreReplaceException $expected): void
    {
        try {
            $fn();
            TestCase::fail('Expected PreReplaceException');
        } catch (PreReplaceException $pre) {
            TestCase::assertEquals($expected->getMessage(), $pre->getMessage());
            TestCase::assertSame($expected->node, $pre->node);
            TestCase::assertSame($expected->parent, $pre->parent);
            TestCase::assertSame($expected->old, $pre->old);
        }
    }
}
