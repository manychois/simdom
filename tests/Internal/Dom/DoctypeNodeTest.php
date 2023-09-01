<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\Dom;
use Manychois\Simdom\NodeType;
use PHPUnit\Framework\TestCase;

class DoctypeNodeTest extends TestCase
{
    /**
     * @dataProvider provideConstructorArgsProvider
     *
     * @param string $name
     * @param string $publicId
     * @param string $systemId
     * @param string $expected
     *
     * @return void
     */
    public function testConstructorInvalidArgsExpectsEx(
        string $name,
        string $publicId,
        string $systemId,
        string $expected
    ): void {
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage($expected);
        Dom::createDocumentType($name, $publicId, $systemId);
    }

    public static function provideConstructorArgsProvider(): Generator
    {
        yield ['a>b', '', '', 'Invalid character ">" in document type name.'];
        yield ['HTML', 'a>b', '', 'Invalid character ">" in document type public identifier.'];
        yield ['HTML', '', 'a>b', 'Invalid character ">" in document type system identifier.'];
    }

    public function testNodeType(): void
    {
        $doctype = Dom::createDocumentType();
        static::assertSame(NodeType::DocumentType, $doctype->nodeType());
    }

    public function testToHtml(): void
    {
        $doctype = Dom::createDocumentType();
        static::assertSame('<!DOCTYPE html>', $doctype->toHtml());
        $doctype = Dom::createDocumentType('HTML', 'a', 'b');
        static::assertSame('<!DOCTYPE HTML PUBLIC "a" "b">', $doctype->toHtml());
        $doctype = Dom::createDocumentType('HTML', '', 'b');
        static::assertSame('<!DOCTYPE HTML SYSTEM "b">', $doctype->toHtml());
    }
}
