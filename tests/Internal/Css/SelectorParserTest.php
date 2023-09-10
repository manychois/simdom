<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Css;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\Internal\Css\AttributeSelector;
use Manychois\Simdom\Internal\Css\AttrMatcher;
use Manychois\Simdom\Internal\Css\ClassSelector;
use Manychois\Simdom\Internal\Css\ComplexSelector;
use Manychois\Simdom\Internal\Css\CompoundSelector;
use Manychois\Simdom\Internal\Css\IdSelector;
use Manychois\Simdom\Internal\Css\OrSelector;
use Manychois\Simdom\Internal\Css\SelectorParser;
use Manychois\Simdom\Internal\Css\TypeSelector;
use PHPUnit\Framework\TestCase;

class SelectorParserTest extends TestCase
{
    public function testParseAttributeSelector1(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('[attr1]');
        assert($selector instanceof AttributeSelector);
        static::assertSame('attr1', $selector->name);
        static::assertSame('', $selector->value);
        static::assertSame(AttrMatcher::Exists, $selector->matcher);
        static::assertTrue($selector->caseSensitive);
    }

    public function testParseAttributeSelector2(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('[abc-def="123"]');
        assert($selector instanceof AttributeSelector);
        static::assertSame('abc-def', $selector->name);
        static::assertSame('123', $selector->value);
        static::assertSame(AttrMatcher::Equals, $selector->matcher);
        static::assertTrue($selector->caseSensitive);

        $selector = $parser->parse('[ ghi = \'456\']');
        assert($selector instanceof AttributeSelector);
        static::assertSame('ghi', $selector->name);
        static::assertSame('456', $selector->value);
        static::assertSame(AttrMatcher::Equals, $selector->matcher);
        static::assertTrue($selector->caseSensitive);
    }

    public function testParseAttributeSelector3(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('[ ABC ~= DEF i]');
        assert($selector instanceof AttributeSelector);
        static::assertSame('abc', $selector->name);
        static::assertSame('DEF', $selector->value);
        static::assertSame(AttrMatcher::Includes, $selector->matcher);
        static::assertFalse($selector->caseSensitive);
    }

    /**
     * @dataProvider dataForParseAttributeSelectorEx1
     */
    public function testParseAttributeSelectorEx1(string $input, string $exMsg): void
    {
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage($exMsg);
        $parser = new SelectorParser();
        $parser->parse($input);
    }

    public static function dataForParseAttributeSelectorEx1(): Generator
    {
        yield ['[', 'Invalid attribute selector found'];
        yield ['[a=', 'Invalid attribute selector found'];
        yield ['[a=]', 'Attribute selector value is missing'];
        yield ['[a=?]', 'Invalid attribute selector value found'];
        yield ['[a=b x]', 'Invalid attribute selector found'];
    }

    public function testParseIdSelector1(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('#id-1');
        assert($selector instanceof IdSelector);
        static::assertSame('id-1', $selector->id);
    }

    public function testParseIdSelectorEx1(): void
    {
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('Invalid ID selector found');
        $parser = new SelectorParser();
        $parser->parse('#');
    }

    public function testParseClassSelector1(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('.display-block');
        assert($selector instanceof ClassSelector);
        static::assertSame('display-block', $selector->cssClass);
    }

    public function testParseClassSelectorEx1(): void
    {
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('Invalid class selector found');
        $parser = new SelectorParser();
        $parser->parse('.?');
    }

    public function testParseTypeSelector1(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('DIV');
        assert($selector instanceof TypeSelector);
        static::assertSame('div', $selector->type);
    }

    public function testParseTypeSelector2(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('*');
        assert($selector instanceof TypeSelector);
        static::assertSame('*', $selector->type);
    }

    public function testParseCompoundSelector1(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('img#id-1.display-block[data-src]');
        assert($selector instanceof CompoundSelector);
        static::assertSame('img', $selector->type?->type);
        static::assertCount(3, $selector->selectors);

        $subclass = $selector->selectors[0];
        assert($subclass instanceof IdSelector);
        static::assertSame('id-1', $subclass->id);

        $subclass = $selector->selectors[1];
        assert($subclass instanceof ClassSelector);
        static::assertSame('display-block', $subclass->cssClass);

        $subclass = $selector->selectors[2];
        assert($subclass instanceof AttributeSelector);
        static::assertSame('data-src', $subclass->name);
        static::assertSame('', $subclass->value);
        static::assertSame(AttrMatcher::Exists, $subclass->matcher);
        static::assertTrue($subclass->caseSensitive);
    }

    public function testParseComplexSelector1(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('a.b  >  #d  +  e  f[readonly]');
        static::assertInstanceOf(ComplexSelector::class, $selector);
        static::assertEquals('a.b>#d+e f[readonly]', $selector->__toString());
    }

    public function testParseComplexSelectorEx1(): void
    {
        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('Missing complex selector after combinator ">"');
        $parser = new SelectorParser();
        $parser->parse('a>');
    }

    public function testParseOrSelector1(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('a.b , c > d');
        static::assertInstanceOf(OrSelector::class, $selector);
        static::assertEquals('a.b,c>d', $selector->__toString());
    }

    public function testUnescape(): void
    {
        $parser = new SelectorParser();
        $selector = $parser->parse('a\\.b\\002b c');
        assert($selector instanceof TypeSelector);
        static::assertSame('a.b+c', $selector->type);
    }

    public function testUnescapeEx(): void
    {
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('Invalid code point 110000 found');
        $parser = new SelectorParser();
        $parser->parse('\\110000');
    }

    /**
     * @dataProvider provideInvalidSelector
     */
    public function testInvalidSelector(string $input, string $expMsg): void
    {
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage($expMsg);
        $parser = new SelectorParser();
        $parser->parse($input);
    }

    public static function provideInvalidSelector(): Generator
    {
        yield ['', 'Invalid selector: '];
        yield ['.', 'Invalid class selector found'];
        yield ['#', 'Invalid ID selector found'];
        yield ['[', 'Invalid attribute selector found'];
        yield ['[a="b]', 'Invalid attribute selector value found'];
        yield ['a%b', 'Invalid character found: %'];
        yield ['%', 'Invalid character found: %'];
    }
}
