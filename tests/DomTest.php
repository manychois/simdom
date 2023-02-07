<?php

namespace Manychois\SimdomTests;

use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class DomTest extends TestCase
{
    public function testPrintWithoutPrettyPrint(): void
    {
        $parser = Dom::createParser('');
        $doc = $parser->parseFromString('');
        $output = Dom::print($doc);
        static::assertSame('<html><head></head><body></body></html>', $output);
    }
}