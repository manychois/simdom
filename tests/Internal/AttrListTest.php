<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Internal\ElementNode;
use PHPUnit\Framework\TestCase;

class AttrListTest extends TestCase
{
    public function testItem(): void
    {
        $e = Dom::createElement('div');
        $e->setAttribute('class', 'foo bar');
        $e->idSet('div-1');
        static::assertSame('class', $e->attributes()->item(0)->name());
        static::assertSame('foo bar', $e->attributes()->item(0)->value());
        static::assertSame('id', $e->attributes()->item(1)->name());
        static::assertSame('div-1', $e->attributes()->item(1)->value());
        static::assertNull($e->attributes()->item(2));
    }

    public function testCountAndLength(): void
    {
        $e = Dom::createElement('div');
        $e->setAttribute('class', 'foo bar');
        $e->idSet('div-1');
        static::assertCount(2, $e->attributes());
        static::assertSame(2, $e->attributes()->length());
    }

    public function testRemoveNotFoundNamedItemExpectsEx(): void
    {
        $e = Dom::createElement('div');
        $ex = new InvalidArgumentException('Attr class not found.');
        $this->expectExceptionObject($ex);
        $e->attributes()->removeNamedItem('class');
    }

    public function testRemoveNotFoundNamedItemNSExpectsEx(): void
    {
        $e = Dom::createElement('div');
        $ex = new InvalidArgumentException('Attr class not found.');
        $this->expectExceptionObject($ex);
        $e->attributes()->removeNamedItemNS(null, 'class');
    }

    public function testSetNamedItemOfUsedAttrExpectsEx(): void
    {
        $div1 = Dom::createElement('div');
        $div1->idSet('div-1');
        $attr = $div1->attributes()->item(0);
        $div2 = Dom::createElement('div');
        $ex = new InvalidArgumentException('Attr id is already in use.');
        $this->expectExceptionObject($ex);
        $div2->attributes()->setNamedItem($attr);
    }

    public function testSetNamedItemOfItsAttrIsOk(): void
    {
        $div1 = Dom::createElement('div');
        $div1->idSet('div-1');
        $attr = $div1->attributes()->item(0);
        $returned = $div1->attributes()->setNamedItem($attr);
        static::assertSame($attr, $returned);

        $newAttr = Dom::createAttr('id', 'div-2');
        $returned = $div1->attributes()->setNamedItem($newAttr);
        static::assertSame($attr, $returned);
        static::assertNull($attr->ownerElement());
        static::assertSame('div-2', $div1->id());
    }

    public function testSetNS(): void
    {
        $e = new ElementNode('div');
        $attr1 = $e->attributes()->setNS(DomNs::XMLNS, null, 'xmlns', 'http://example-a.com');
        $attr2 = $e->attributes()->setNS(DomNs::XMLNS, null, 'xmlns', 'http://example-b.com');
        static::assertSame($attr1, $attr2);
        static::assertSame('http://example-b.com', $attr1->value());
    }
}
