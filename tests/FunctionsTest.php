<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use InvalidArgumentException;
use Manychois\Simdom\Element;

use function Manychois\Simdom\append;
use function Manychois\Simdom\e;
use function Manychois\Simdom\parseElement;

/**
 * @internal
 *
 * @coversNothing
 */
class FunctionsTest extends AbstractBaseTestCase
{
    public function testAppend(): void
    {
        $func1 = fn () => 'Test string';
        $func2 = fn () => Element::create('span');
        $func3 = fn () => null;
        $div = Element::create('div');
        append($div, [$func1, null, $func2, $func3]);
        $this->assertEquals('<div>Test string<span></span></div>', $div->__toString());
    }

    public function testAppendWithInvalidCallableReturn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Child nodes must be string, AbstractNode, callable, iterable, or null.');
        $div = Element::create('div');
        append($div, fn () => 123);
    }

    public function testAppendWithInvalidIterableItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Child nodes must be string, AbstractNode, callable, iterable, or null.');
        $div = Element::create('div');
        append($div, [Element::create('span'), 123]);
    }

    public function testEWithStringAttributesAndStringChildNodes(): void
    {
        $div = e('div', 'container main', 'Hello, World!');
        $this->assertSame('div', $div->name);
        $this->assertSame('container main', $div->getAttr('class'));
        $this->assertCount(1, $div->childNodes);
        $node = $div->childNodes->at(0);
        assert(null !== $node);
        $this->assertSame('Hello, World!', $node->__toString());
    }

    public function testEWithMultipleAttrs(): void
    {
        $div = e('div', [
            'id' => 'main',
            'class' => 'container',
            'contenteditable',
            'skipped' => null,
            'data-value' => ['one', 'two', 'three'],
        ]);
        $this->assertSame('<div id="main" class="container" contenteditable="" data-value=\'["one","two","three"]\'></div>', $div->__toString());
    }

    public function testEWithInvalidAttr(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute name must be a string.');
        e('div', [123]);
    }

    public function testParseElement(): void
    {
        $html = <<<'HTML'
            <div id="main" class="container">
              <p>Hello, <span>World!</span></p>
            </div>
            HTML;
        $div = parseElement($html);
        $this->assertSame('div', $div->name);
        $this->assertSame('main', $div->getAttr('id'));
        $this->assertSame('container', $div->getAttr('class'));
        $p = $div->firstElementChild;
        assert($p instanceof Element);
        $this->assertSame('p', $p->name);
    }

    public function testParseElementWithoutRootElement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided HTML does not contain a valid root element.');
        parseElement('12345');
    }
}
