<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\UnitTests;

use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\Element;
use Manychois\Simdom\Text;
use PHPUnit\Framework\TestCase;

class ChildNodeListTest extends TestCase
{
    public function testGet(): void
    {
        $owner = new Element('div');
        $list = $owner->childNodeList;
        $list->🚫append($owner, 'a', 'b', 'c');

        static::assertSame('a', self::getTextNodeData($list->get(0)));
        static::assertSame('b', self::getTextNodeData($list->get(1)));
        static::assertSame('c', self::getTextNodeData($list->get(2)));
        static::assertNull($list->get(3));
        static::assertSame('c', self::getTextNodeData($list->get(-1)));
        static::assertSame('b', self::getTextNodeData($list->get(-2)));
        static::assertSame('a', self::getTextNodeData($list->get(-3)));
        static::assertNull($list->get(-4));
    }

    public function testReverse(): void
    {
        $owner = new Element('div');
        $list = $owner->childNodeList;
        $list->🚫append($owner, 'a', 'b', 'c');

        $reversed = \iterator_to_array($list->reverse());
        static::assertSame('c', self::getTextNodeData($reversed[0]));
        static::assertSame('b', self::getTextNodeData($reversed[1]));
        static::assertSame('a', self::getTextNodeData($reversed[2]));
    }

    public function test🚫InsertAt(): void
    {
        $owner = new Element('div');
        $list = $owner->childNodeList;
        $list->🚫append($owner, 'a', 'b', 'c');
        $list->🚫insertAt($owner, 1, 'x', 'y');

        static::assertCount(5, $list);
        static::assertSame('a', self::getTextNodeData($list->get(0)));
        static::assertSame('x', self::getTextNodeData($list->get(1)));
        static::assertSame('y', self::getTextNodeData($list->get(2)));
        static::assertSame('b', self::getTextNodeData($list->get(3)));
        static::assertSame('c', self::getTextNodeData($list->get(4)));

        for ($i = 0; $i < 5; $i++) {
            static::assertSame($owner, $list->get($i)->parent());
            static::assertSame($i, $list->get($i)->index());
        }
    }

    public function test🚫Remove(): void
    {
        $owner = new Element('div');
        $list = $owner->childNodeList;
        $list->🚫append($owner, 'a', 'b', 'c', 'd', 'e');
        $a = $list->get(0);
        $b = $list->get(1);
        $c = $list->get(2);
        $d = $list->get(3);
        $e = $list->get(4);

        $list->🚫remove($c);
        static::assertCount(4, $list);
        static::assertSame($a, $list->get(0));
        static::assertSame($b, $list->get(1));
        static::assertSame($d, $list->get(2));
        static::assertSame(2, $d->index());
        static::assertSame($e, $list->get(3));
        static::assertSame(3, $e->index());
        static::assertNull($c->parent());
        static::assertSame(-1, $c->index());

        $list->🚫remove($e, $a);
        static::assertCount(2, $list);
        static::assertSame($b, $list->get(0));
        static::assertSame(0, $b->index());
        static::assertSame($d, $list->get(1));
        static::assertSame(1, $d->index());
        static::assertNull($a->parent());
        static::assertSame(-1, $a->index());
        static::assertNull($e->parent());
        static::assertSame(-1, $e->index());

        // not triggering any error
        $list->🚫remove();
    }

    private static function getTextNodeData(AbstractNode $n): string
    {
        return $n instanceof Text ? $n->data : '';
    }
}
