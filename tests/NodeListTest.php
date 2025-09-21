<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use Generator;
use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\Element;
use Manychois\Simdom\NodeList;
use Manychois\Simdom\Text;
use PHPUnit\Framework\Attributes\DataProvider;

use function Manychois\Simdom\e;

/**
 * @internal
 *
 * @coversNothing
 */
class NodeListTest extends AbstractBaseTestCase
{
    #[DataProvider('provideEqualsData')]
    public function testEquals(bool $expected, NodeList $a, NodeList $b): void
    {
        $this->assertSame($expected, $a->equals($b));
    }

    public static function provideEqualsData(): Generator
    {
        $a = Element::create('a');
        $b = Element::create('b');
        $a->append('123');
        yield 'unequal count' => [false, $a->childNodes, $b->childNodes];
        $c = Element::create('c');
        $c->append('234');
        yield 'unequal content' => [false, $a->childNodes, $c->childNodes];
        $d = Element::create('d');
        $d->append('123');
        yield 'equal' => [true, $a->childNodes, $d->childNodes];
    }

    public function testFindElement(): void
    {
        $a = e('span', 'a');
        $b = e('span', 'b');
        $c1 = e('span', 'c');
        $c2 = e('span', 'c');
        $d = e('span', 'd');
        $root = e('div', [], [$a, $b, $c1, $c2, $d]);

        $acutal = $root->childNodes->findElement(fn (Element $el) => 'c' === $el->className);
        $this->assertSame($c1, $acutal);
        $acutal = $root->childNodes->findElement(fn (Element $el) => 'x' === $el->className);
        $this->assertNull($acutal);
    }

    public function testFindLast(): void
    {
        $root = e('div', [], ['1', '2', '3', '4', '5']);

        $acutal = $root->childNodes->findLast(fn (AbstractNode $n) => $n instanceof Text);
        assert($acutal instanceof Text);
        $this->assertSame('5', $acutal->data);
        $acutal = $root->childNodes->findLast(fn (AbstractNode $n) => $n instanceof Element);
        $this->assertNull($acutal);
    }

    public function testFindLastElement(): void
    {
        $a = e('span', 'a');
        $b = e('span', 'b');
        $c1 = e('span', 'c');
        $c2 = e('span', 'c');
        $d = e('span', 'd');
        $root = e('div', [], [$a, $b, $c1, $c2, $d]);

        $acutal = $root->childNodes->findLastElement(fn (Element $el) => 'c' === $el->className);
        $this->assertSame($c2, $acutal);
        $acutal = $root->childNodes->findLastElement(fn (Element $el) => 'x' === $el->className);
        $this->assertNull($acutal);
    }
}
