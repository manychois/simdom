<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class ClassListTest extends TestCase
{
    public function testAttrLinking(): void
    {
        $e = Dom::createElement('div');
        static::assertNull($e->getAttribute('class'));
        static::assertCount(0, $e->classList());
        $e->classList()->add('foo', 'bar');
        static::assertSame('foo bar', $e->getAttribute('class'));
        $e->setAttribute('class', 'foo-bar foo bar');
        static::assertCount(3, $e->classList());
        static::assertSame(3, $e->classList()->length());
        static::assertSame('foo-bar', $e->classList()->item(0));
        static::assertSame('foo', $e->classList()->item(1));
        static::assertSame('bar', $e->classList()->item(2));
    }

    public function testAdd(): void
    {
        $e = Dom::createElement('div');
        $e->classList()->add('foo', 'bar', 'foo');
        static::assertCount(2, $e->classList());
        static::assertSame('foo bar', $e->getAttribute('class'));
        static::assertSame('foo', $e->classList()->item(0));
        static::assertSame('bar', $e->classList()->item(1));

        $e->classList()->add('foo', 'bar', 'foo-bar');
        static::assertCount(3, $e->classList());
        static::assertSame('foo bar foo-bar', $e->getAttribute('class'));
        static::assertSame('foo', $e->classList()->item(0));
        static::assertSame('bar', $e->classList()->item(1));
        static::assertSame('foo-bar', $e->classList()->item(2));
    }

    public function testContains(): void
    {
        $e = Dom::createElement('div');
        $e->setAttribute('class', 'foo bar');
        static::assertTrue($e->classList()->contains('foo'));
        static::assertTrue($e->classList()->contains('bar'));
        static::assertFalse($e->classList()->contains('foo-bar'));
    }

    public function testContainsEmptyTokenExpectsEx(): void
    {
        $ex = new InvalidArgumentException('The token must not be empty.');
        $this->expectExceptionObject($ex);
        $e = Dom::createElement('div');
        $e->classList()->contains('');
    }

    public function testContainsTokenHavingSpaceExpectsEx(): void
    {
        $ex = new InvalidArgumentException('The token must not contain any whitespace.');
        $this->expectExceptionObject($ex);
        $e = Dom::createElement('div');
        $e->classList()->contains('foo bar');
    }

    public function testRemove(): void
    {
        $e = Dom::createElement('div');
        $e->setAttribute('class', 'foo bar foo-bar');
        $attr = $e->getAttributeNode('class');
        static::assertSame('foo bar foo-bar', $attr->value());

        $e->classList()->remove('foo', 'foo-bar', 'foo');
        static::assertCount(1, $e->classList());
        static::assertSame('bar', $e->getAttribute('class'));
        static::assertSame('bar', $attr->value());

        $e->classList()->remove('bar');
        static::assertCount(0, $e->classList());
        static::assertSame('', $e->getAttribute('class'));
        static::assertSame('', $attr->value());
    }

    public function testReplace(): void
    {
        $e = Dom::createElement('div');
        $e->setAttribute('class', 'foo bar foo-bar');
        $replaced = $e->classList()->replace('foo', 'new-foo');
        static::assertTrue($replaced);
        static::assertCount(3, $e->classList());
        static::assertSame('new-foo bar foo-bar', $e->getAttribute('class'));

        $replaced = $e->classList()->replace('bar', 'bar');
        static::assertFalse($replaced);
        static::assertCount(3, $e->classList());
        static::assertSame('new-foo bar foo-bar', $e->getAttribute('class'));

        $replaced = $e->classList()->replace('new-bar', 'old-bar');
        static::assertFalse($replaced);
        static::assertCount(3, $e->classList());
        static::assertSame('new-foo bar foo-bar', $e->getAttribute('class'));
    }

    public function testToggle(): void
    {
        $e = Dom::createElement('div');
        static::assertSame(null, $e->getAttributeNode('class'));
        $isOn = $e->classList()->toggle('foo');
        static::assertTrue($isOn);
        static::assertSame('foo', $e->getAttribute('class'));
        $attr = $e->getAttributeNode('class');
        static::assertSame('foo', $attr->value());

        $isOn = $e->classList()->toggle('foo');
        static::assertFalse($isOn);
        static::assertSame('', $e->getAttribute('class'));
        static::assertSame('', $attr->value());
    }
}
