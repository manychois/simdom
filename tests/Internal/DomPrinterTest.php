<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\DomPrinter;
use Manychois\Simdom\PrettyPrintOption;
use PHPUnit\Framework\TestCase;

class DomPrinterTest extends TestCase
{
    private static function getSampleDir(string $funcName): string
    {
        $dirName = preg_replace('/^test/', '', $funcName);
        $dirName = strtolower($dirName[0]) . substr($dirName, 1);
        return __DIR__ . '/dom-printer-test-samples/' . $dirName;
    }

    private static function runTestCase(string $srcFilename, PrettyPrintOption $option): void
    {
        $html = file_get_contents(__DIR__ . "/dom-printer-test-samples/$srcFilename.html");
        $parser = Dom::createParser();
        $doc = $parser->parseFromString($html);
        $printer = new DomPrinter();
        $output = $printer->print($doc, $option);
        $expected = file_get_contents(__DIR__ . "/dom-printer-test-samples/$srcFilename-formatted.html");
        static::assertSame($expected, $output);
    }

    public function testGetDocumentTypeString(): void
    {
        $doctype = Dom::createDocumentType('a', 'b', 'c');
        $printer = new DomPrinter();
        $output = $printer->getDocumentTypeString($doctype);
        static::assertSame('<!DOCTYPE a PUBLIC "b" "c">', $output);
        $doctype = Dom::createDocumentType('a', '', 'b');
        $output = $printer->getDocumentTypeString($doctype);
        static::assertSame('<!DOCTYPE a SYSTEM "b">', $output);
    }

    public function testAttrWithEscOn(): void
    {
        static::runTestCase('attr-esc-on', new PrettyPrintOption());
    }

    public function testAttrWithEscOff(): void
    {
        $option = new PrettyPrintOption();
        $option->escAttrValue = false;
        static::runTestCase('attr-esc-off', $option);
    }

    public function testDoctype(): void
    {
        static::runTestCase('comment-before-doctype', new PrettyPrintOption());
    }

    public function testPre(): void
    {
        static::runTestCase('pre', new PrettyPrintOption());
    }

    public function testSvg(): void
    {
        static::runTestCase('svg', new PrettyPrintOption());
    }

    public function testInlineElement(): void
    {
        static::runTestCase('p', new PrettyPrintOption());
    }
}
