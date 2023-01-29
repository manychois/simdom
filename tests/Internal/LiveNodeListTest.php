<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Text;
use PHPUnit\Framework\TestCase;

class LiveNodeListTest extends TestCase
{
    public function testFindIndex(): void
    {
        $div = Dom::createElement('div');
        $div->append('a', 'b', 'c', 'd', 'e');
        $i = $div->childNodes()->findIndex(fn ($n) => $n instanceof Text, -2);
        static::assertSame(3, $i);
        $i = $div->childNodes()->findIndex(fn ($n) => $n instanceof Text, -10);
        static::assertSame(0, $i);
        $i = $div->childNodes()->findIndex(fn ($n) => $n instanceof Text && $n->data() === 'c');
        static::assertSame(2, $i);
        $i = $div->childNodes()->findIndex(fn ($n) => $n instanceof Text && $n->data() === 'f');
        static::assertSame(-1, $i);
    }

    public function testFindLastIndex(): void
    {
        $div = Dom::createElement('div');
        $div->append('a', 'b', 'c', 'd', 'e');
        $i = $div->childNodes()->findLastIndex(fn ($n) => $n instanceof Text, -2);
        static::assertSame(3, $i);
        $i = $div->childNodes()->findLastIndex(fn ($n) => $n instanceof Text, 10);
        static::assertSame(4, $i);
        $i = $div->childNodes()->findLastIndex(fn ($n) => $n instanceof Text, -6);
        static::assertSame(-1, $i);
        $i = $div->childNodes()->findLastIndex(fn ($n) => $n instanceof Text && $n->data() === 'c');
        static::assertSame(2, $i);
        $i = $div->childNodes()->findLastIndex(fn ($n) => $n instanceof Text && $n->data() === 'f');
        static::assertSame(-1, $i);
    }
}
