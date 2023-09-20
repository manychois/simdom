<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Css;

use Generator;
use Manychois\Simdom\Dom;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\Css\ClassSelector;
use Manychois\Simdom\Internal\Css\CompoundSelector;
use Manychois\Simdom\Internal\Css\TypeSelector;
use PHPUnit\Framework\TestCase;

class CompoundSelectorTest extends TestCase
{
    /**
     * @dataProvider provideMatchWith
     */
    public function testMatchWith(CompoundSelector $s, ElementInterface $e, bool $expected): void
    {
        static::assertSame($expected, $s->matchWith($e));
    }

    public static function provideMatchWith(): Generator
    {
        $s = new CompoundSelector();
        $s->type = new TypeSelector('p');
        $s->selectors[] = new ClassSelector('para');

        $e = Dom::createElement('a');
        $e->setAttribute('class', 'para');
        yield [$s, $e, false];

        $e  = Dom::createElement('p');
        yield [$s, $e, false];

        $e = Dom::createElement('p');
        $e->setAttribute('class', 'para');
        yield [$s, $e, true];
    }
}
