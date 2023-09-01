<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class ElementNodeTest extends TestCase
{
    public function testSetAttribute(): void
    {
        $div = Dom::createElement('div');
        $div->setAttribute('ID', 'id-1');
        static::assertSame('id-1', $div->getAttribute('id'));
        $div->setAttribute('iD', 'id-2');
        static::assertSame('id-2', $div->getAttribute('ID'));

        foreach ($div->attributes() as $name => $value) {
            static::assertSame('id', $name);
            static::assertSame('id-2', $value);
        }
    }

    public function testAppendExpectsException(): void
    {
        $div = Dom::createElement('div');
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('DocumentType cannot be a child of an Element.');
        $div->append(Dom::createDocumentType());
    }

    public function testReplaceExpectsException(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createElement('a');
        $div->append($a);
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('DocumentType cannot be a child of an Element.');
        $div->replace($a, Dom::createDocumentType());
    }

    public function testToHtml(): void
    {
        $input = Dom::createElement('input');
        $input->setAttribute('disabled', null);
        $input->setAttribute('value', 'A & B');
        static::assertSame('<input disabled value="A &amp; B">', $input->toHtml());

        $div = Dom::createElement('div');
        $div->setAttribute('class', 'input');
        $div->append('A & B', $input);
        static::assertSame('<div class="input">A &amp; B<input disabled value="A &amp; B"></div>', $div->toHtml());

        $script = Dom::createElement('script');
        $script->append('console.log("A & B");');
        static::assertSame('<script>console.log("A & B");</script>', $script->toHtml());
    }
}
