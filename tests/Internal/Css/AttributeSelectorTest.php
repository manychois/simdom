<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Css;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\Css\AttributeSelector;
use Manychois\Simdom\Internal\Css\AttrMatcher;
use PHPUnit\Framework\TestCase;

class AttributeSelectorTest extends TestCase
{
    public function testMatchWithExists(): void
    {
        $s = new AttributeSelector('id', AttrMatcher::Exists, '', false);
        static::assertSame('[id]', $s->__toString());

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('id', 'foo');
        static::assertTrue($s->matchWith($el));
    }

    public function testMatchWithEquals(): void
    {
        $s = new AttributeSelector('id', AttrMatcher::Equals, 'foo', false);
        static::assertSame('[id="foo" i]', $s->__toString());

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('id', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('id', 'FOO');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('id', 'bar');
        static::assertFalse($s->matchWith($el));

        $s = new AttributeSelector('id', AttrMatcher::Equals, 'foo', true);
        static::assertSame('[id="foo"]', $s->__toString());

        $el->setAttribute('id', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('id', 'FOO');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('id', 'bar');
        static::assertFalse($s->matchWith($el));
    }

    public function testMatchWithIncludes(): void
    {
        $s = new AttributeSelector('class', AttrMatcher::Includes, 'foo', false);
        static::assertSame('[class~="foo" i]', $s->__toString());

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'foo bar');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar FOO baz');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar baz');
        static::assertFalse($s->matchWith($el));

        $s = new AttributeSelector('class', AttrMatcher::Includes, 'foo', true);
        static::assertSame('[class~="foo"]', $s->__toString());

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'foo bar');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar FOO baz');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'bar baz');
        static::assertFalse($s->matchWith($el));
    }

    public function testMatchWithDashMatch(): void
    {
        $s = new AttributeSelector('lang', AttrMatcher::DashMatch, 'en', false);
        static::assertSame('[lang|="en" i]', $s->__toString());

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('lang', 'en');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('lang', 'EN-US');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('lang', 'en_US');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('lang', 'foo-en');
        static::assertFalse($s->matchWith($el));

        $s = new AttributeSelector('lang', AttrMatcher::DashMatch, 'en', true);
        static::assertSame('[lang|="en"]', $s->__toString());

        $el->setAttribute('lang', 'en');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('lang', 'EN-US');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('lang', 'en_US');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('lang', 'foo-en');
        static::assertFalse($s->matchWith($el));
    }

    public function testMatchWithPrefixMatch(): void
    {
        $s = new AttributeSelector('class', AttrMatcher::PrefixMatch, 'foo', false);
        static::assertSame('[class^="foo" i]', $s->__toString());

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'foobar');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'FooBar');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar foo');
        static::assertFalse($s->matchWith($el));

        $s = new AttributeSelector('class', AttrMatcher::PrefixMatch, 'foo', true);
        static::assertSame('[class^="foo"]', $s->__toString());

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'foobar');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'FooBar');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'bar foo');
        static::assertFalse($s->matchWith($el));
    }

    public function testMatchWithSuffixMatch(): void
    {
        $s = new AttributeSelector('class', AttrMatcher::SuffixMatch, 'foo', false);
        static::assertSame('[class$="foo" i]', $s->__toString());

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barfoo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barFoo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'foo bar');
        static::assertFalse($s->matchWith($el));

        $s = new AttributeSelector('class', AttrMatcher::SuffixMatch, 'foo', true);
        static::assertSame('[class$="foo"]', $s->__toString());

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barfoo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barFoo');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo bar');
        static::assertFalse($s->matchWith($el));
    }

    public function testMatchWithSubstringMatch(): void
    {
        $s = new AttributeSelector('class', AttrMatcher::SubstringMatch, 'foo', false);
        static::assertSame('[class*="foo" i]', $s->__toString());

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barfoo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barFoo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'foo bar');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar');
        static::assertFalse($s->matchWith($el));

        $s = new AttributeSelector('class', AttrMatcher::SubstringMatch, 'foo', true);
        static::assertSame('[class*="foo"]', $s->__toString());

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barfoo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'barFoo');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo bar');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'bar');
        static::assertFalse($s->matchWith($el));
    }
}
