<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\DoctypeNode;
use PHPUnit\Framework\TestCase;

class DoctypeNodeTest extends TestCase
{
    public function testTextContent(): void
    {
        $doctype = Dom::createDocumentType('html', 'a', 'b');
        $this->assertNull($doctype->textContent());
        $doctype->textContentSet('abc');
        $this->assertNull($doctype->textContent());
        static::assertSame('html', $doctype->name());
        static::assertSame('a', $doctype->publicId());
        static::assertSame('b', $doctype->systemId());
    }

    public function testSerialize(): void
    {
        $doctype = new DoctypeNode('html', '', 'a');
        $this->assertSame('<!DOCTYPE html SYSTEM "a">', $doctype->serialize());
        $doctype = new DoctypeNode('html', 'b', 'c');
        $this->assertSame('<!DOCTYPE html PUBLIC "b" "c">', $doctype->serialize());
    }

    public function testCloneNode(): void
    {
        $doctype = Dom::createDocumentType('html', 'a', 'b');
        $clone = $doctype->cloneNode();
        $this->assertNotSame($doctype, $clone);
        $this->assertSame('html', $clone->name());
        $this->assertSame('a', $clone->publicId());
        $this->assertSame('b', $clone->systemId());
    }

    public function testIsEqualNode(): void
    {
        $a = Dom::createDocumentType('html', 'a', 'b');
        $b = Dom::createDocumentType('html', 'a', '');
        $c = Dom::createDocumentType('html', 'a', '');
        $this->assertFalse($a->isEqualNode($b));
        $this->assertTrue($b->isEqualNode($c));
    }
}
