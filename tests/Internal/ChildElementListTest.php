<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\SimdomTests\TestUtility;
use PHPUnit\Framework\TestCase;

class ChildElementListTest extends TestCase
{
    public function testOnNodeListCleared(): void
    {
        $div = Dom::createElement('div');
        $p = Dom::createElement('p');
        $div->append('1', '2', $p, '3');
        static::assertSame($p, $div->children()->item(0));
        $div->childNodes()->clear();
        TestUtility::assertCount(0, $div->children());
    }

    public function testOnNodeListInserted(): void
    {
        $div = Dom::createElement('div');
        TestUtility::assertCount(0, $div->children());
        $p = Dom::createElement('p');
        $div->append('3');
        $div->insertBefore($p, $div->firstChild());
        TestUtility::assertCount(1, $div->children());
        static::assertSame($p, $div->children()->item(0));

        $i = Dom::createElement('i');
        $div->insertBefore($i, $p);
        TestUtility::assertCount(2, $div->children());
        static::assertSame($i, $div->children()->item(0));
        static::assertSame($p, $div->children()->item(1));

        $a = Dom::createElement('a');
        $div->insertBefore($a, $p);
        TestUtility::assertCount(3, $div->children());
        static::assertSame($i, $div->children()->item(0));
        static::assertSame($a, $div->children()->item(1));
        static::assertSame($p, $div->children()->item(2));

        $comment = Dom::createComment('comment');
        $div->insertBefore($comment, $a);
        TestUtility::assertCount(3, $div->children());
        static::assertSame($i, $div->children()->item(0));
        static::assertSame($a, $div->children()->item(1));
        static::assertSame($p, $div->children()->item(2));
    }

    public function testOnNodeListRemoved(): void
    {
        $div = Dom::createElement('div');
        $p = Dom::createElement('p');
        $a = Dom::createElement('a');
        $i = Dom::createElement('i');
        $div->append('1', $p, '2', $a, '3', $i, '4');
        TestUtility::assertCount(3, $div->children());
        $div->removeChild($a);
        TestUtility::assertCount(2, $div->children());
        static::assertSame($p, $div->children()->item(0));
        static::assertSame($i, $div->children()->item(1));

        $div->removeChild($div->firstChild());
        TestUtility::assertCount(2, $div->children());
        static::assertSame($p, $div->children()->item(0));
        static::assertSame($i, $div->children()->item(1));
    }
}
