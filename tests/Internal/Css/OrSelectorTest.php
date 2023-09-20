<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Css;

use Generator;
use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\Css\AbstractSelector;
use Manychois\Simdom\Internal\Css\AttributeSelector;
use Manychois\Simdom\Internal\Css\AttrMatcher;
use Manychois\Simdom\Internal\Css\ClassSelector;
use Manychois\Simdom\Internal\Css\Combinator;
use Manychois\Simdom\Internal\Css\ComplexSelector;
use Manychois\Simdom\Internal\Css\CompoundSelector;
use Manychois\Simdom\Internal\Css\IdSelector;
use Manychois\Simdom\Internal\Css\OrSelector;
use Manychois\Simdom\Internal\Css\TypeSelector;
use PHPUnit\Framework\TestCase;

class OrSelectorTest extends TestCase
{
    /**
     * @dataProvider provideGetComplexity
     */
    public function testGetComplexity(AbstractSelector $s, int $expected): void
    {
        static::assertSame($expected, OrSelector::getComplexity($s));
    }

    public static function provideGetComplexity(): Generator
    {
        $type = new TypeSelector('p');
        yield [$type, 1];

        $id = new IdSelector('id-1');
        yield [$id, 2];

        $class = new ClassSelector('class-1');
        yield [$class, 3];

        $attr = new AttributeSelector('attr-1', AttrMatcher::Exists, '', false);
        yield [$attr, 4];

        $compound = new CompoundSelector();
        $compound->type = $type;
        $compound->selectors[] = $id;
        $compound->selectors[] = $class;
        $compound->selectors[] = $attr;
        yield [$compound, 10];

        $complex = new ComplexSelector($compound);
        $complex->combinators[] = Combinator::Descendant;
        $complex->selectors[] = $type;
        $complex->combinators[] = Combinator::Child;
        $complex->selectors[] = $id;
        $complex->combinators[] = Combinator::AdjacentSibling;
        $complex->selectors[] = $class;
        $complex->combinators[] = Combinator::GeneralSibling;
        $complex->selectors[] = $attr;
        yield [$complex, 140];

        $or = new OrSelector();
        $or->selectors[] = $compound;
        $or->selectors[] = $complex;
        yield [$or, 150];
    }

    public function testMatchWith(): void
    {
        $s = new OrSelector();
        $e = Dom::createElement('div');

        static::assertFalse($s->matchWith($e));

        $s->selectors[] = new ClassSelector('foo');

        $e->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($e));

        $s->selectors[] = new ClassSelector('bar');
        $e->setAttribute('class', 'bar');
        static::assertTrue($s->matchWith($e));
        $e->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($e));

        $e->setAttribute('class', 'baz');
        static::assertFalse($s->matchWith($e));
    }
}
