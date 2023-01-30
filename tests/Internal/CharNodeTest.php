<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class CharNodeTest extends TestCase
{
    public function testDataSet(): void
    {
        $text = Dom::createText('foo');
        static::assertSame('foo', $text->data());
        $text->dataSet('bar');
        static::assertSame('bar', $text->data());
    }

    public function testIsEqualNode(): void
    {
        $text = Dom::createText('foo');
        static::assertTrue($text->isEqualNode(Dom::createText('foo')));
        static::assertFalse($text->isEqualNode(Dom::createText('bar')));
        static::assertFalse($text->isEqualNode(Dom::createComment('foo')));
    }

    public function testTextContentSet(): void
    {
        $text = Dom::createText('foo');
        static::assertSame('foo', $text->data());
        $text->textContentSet('bar');
        static::assertSame('bar', $text->data());
    }
}
