<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Css;

use BadMethodCallException;
use Generator;
use InvalidArgumentException;
use Manychois\Simdom\Dom;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\Css\AbstractSelector;
use Manychois\Simdom\Internal\Css\ClassSelector;
use Manychois\Simdom\Internal\Css\Combinator;
use Manychois\Simdom\Internal\Css\ComplexSelector;
use Manychois\Simdom\Internal\Css\OrSelector;
use PHPUnit\Framework\TestCase;

class ComplexSelectorTest extends TestCase
{
    /**
     * @dataProvider provideConstructorEx
     */
    public function testConstructorEx(AbstractSelector $first, string $expMsg): void
    {
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage($expMsg);
        new ComplexSelector($first);
    }

    public static function provideConstructorEx(): Generator
    {
        yield 'first is ComplexSelector' => [
            new ComplexSelector(new ClassSelector('foo')),
            '$first cannot be a ComplexSelector',
        ];
        yield 'first is OrSelector' => [
            new OrSelector(),
            '$first cannot be an OrSelector',
        ];
    }

    /**
     * @dataProvider provideMatchWith
     */
    public function testMatchWith(ComplexSelector $s, ElementInterface $e, bool $expected): void
    {
        static::assertSame($expected, $s->matchWith($e));
    }

    public static function provideMatchWith(): Generator
    {
        $s = new ComplexSelector(new ClassSelector('foo'));
        $e = Dom::createElement('div')->setAttribute('class', 'foo');
        yield [$s, $e, true];

        $s = new ComplexSelector(new ClassSelector('foo'));
        $s->combinators[] = Combinator::Descendant;
        $s->selectors[] = new ClassSelector('bar');
        $e = Dom::createElement('div');
        yield [$s, $e, false];

        $e = Dom::createElement('div')->setAttribute('class', 'bar');
        $e2 = Dom::createElement('div')->setAttribute('class', 'woo');
        $e2->append($e);
        yield [$s, $e, false];

        $s = new ComplexSelector(new ClassSelector('a'));
        $s->combinators = [Combinator::Descendant, Combinator::Descendant];
        array_push($s->selectors, new ClassSelector('b'), new ClassSelector('c'));
        $eC = Dom::createElement('div')->setAttribute('class', 'c');
        $e = Dom::createElement('div');
        $e->append($eC);
        $eB = Dom::createElement('div')->setAttribute('class', 'b');
        $eB->append($e);
        $e = Dom::createElement('div');
        $e->append($eB);
        $eA = Dom::createElement('div')->setAttribute('class', 'a');
        $eA->append($e);
        yield [$s, $eC, true];

        $s = new ComplexSelector(new ClassSelector('a'));
        $s->combinators[] = Combinator::Child;
        $s->selectors[] = new ClassSelector('b');
        $eB = Dom::createElement('div')->setAttribute('class', 'b');
        $eA = Dom::createElement('div')->setAttribute('class', 'a');
        $eA->append($eB);
        yield [$s, $eB, true];

        $s = new ComplexSelector(new ClassSelector('a'));
        $s->combinators[] = Combinator::AdjacentSibling;
        $s->selectors[] = new ClassSelector('b');
        $eA = Dom::createElement('div')->setAttribute('class', 'a');
        $eB = Dom::createElement('div')->setAttribute('class', 'b');
        $e = Dom::createElement('div');
        $e->append($eA, 'text', $eB);
        yield [$s, $eB, true];

        $s = new ComplexSelector(new ClassSelector('a'));
        $s->combinators[] = Combinator::GeneralSibling;
        $s->selectors[] = new ClassSelector('b');
        $eB = Dom::createElement('div')->setAttribute('class', 'b');
        yield [$s, $eB, false];

        $eB = Dom::createElement('div')->setAttribute('class', 'b');
        $eC = Dom::createElement('div')->setAttribute('class', 'c');
        $eA = Dom::createElement('div')->setAttribute('class', 'a');
        $e = Dom::createElement('div');
        $e->append($eA, 'text', $eC, 'text', $eB);
        yield [$s, $eB, true];
    }

    public function testMatchWithEx1(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Combinator "||" is not supported');

        $s = new ComplexSelector(new ClassSelector('a'));
        $s->combinators[] = Combinator::Column;
        $s->selectors[] = new ClassSelector('b');
        $eA = Dom::createElement('div')->setAttribute('class', 'a');
        $eB = Dom::createElement('div')->setAttribute('class', 'b');
        $eA->append($eB);
        $s->matchWith($eB);
    }
}
