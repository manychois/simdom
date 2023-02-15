<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Parsing;

use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class LegacyParserTest extends TestCase
{
    public function testParseFromString(): void
    {
        $parser = Dom::createParser(true);
        $source = <<<'HTML'
<!-- HTML 4 --><html><body><main>Hello, <i class="a">world!</i></main></body></html>
HTML;
        $doc = $parser->parseFromString($source);
        $output = Dom::print($doc);
        $expected = implode('', [
            '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"',
            ' "http://www.w3.org/TR/REC-html40/loose.dtd">',
            '<!-- HTML 4 --><html><body><main>Hello, <i class="a">world!</i></main></body></html>',
        ]);
        static::assertSame($expected, $output);
    }
}
