<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class DomTest extends TestCase
{
    /**
     * @dataProvider provideCreateElementExpectsException
     */
    public function testCreateElementExpectsException(string $tagName, string $errMsg): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($errMsg);
        Dom::createElement($tagName);
    }

    public static function provideCreateElementExpectsException(): Generator
    {
        yield ['', 'Tag name cannot be empty.'];
        yield ['$abc', 'Invalid tag name: $abc'];
    }

    public function testParse(): void
    {
        $doc = Dom::parse('');
        static::assertEquals('<html><head></head><body></body></html>', $doc->toHtml());
    }
}
