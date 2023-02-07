<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\DomPrinter;
use Manychois\Simdom\PrettyPrintOption;
use PHPUnit\Framework\TestCase;

class DomPrinterTest extends TestCase
{
    private static function runTestCase(string $srcFilename, PrettyPrintOption $option): void
    {
        $html = file_get_contents(__DIR__ . "/dom-printer-test-samples/$srcFilename.html");
        $parser = Dom::createParser();
        $doc = $parser->parseFromString($html);
        $output = Dom::print($doc, $option);
        $expected = file_get_contents(__DIR__ . "/dom-printer-test-samples/$srcFilename-formatted.html");
        static::assertSame($expected, $output);
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

    public function testPrintDocumentFragment(): void
    {
        $frag = Dom::createDocumentFragment();
        $e = Dom::createElement('div');
        $frag->appendChild($e);
        $e->appendChild(Dom::createText('Hello'));
        $option = new PrettyPrintOption();
        $option->indent = "\t";
        $output = Dom::print($frag, $option);
        static::assertSame("<div>\n\tHello\n</div>", $output);
    }

    public function testPrintElement(): void
    {
        $e = Dom::createElement('div');
        $e->appendChild(Dom::createText('Hello'));
        $output = Dom::print($e, new PrettyPrintOption());
        static::assertSame("<div>\n  Hello\n</div>", $output);
    }

    public function testPrintText(): void
    {
        $output = Dom::print(Dom::createText('Hello'), new PrettyPrintOption());
        static::assertSame("Hello", $output);
    }

    public function testPrintComment(): void
    {
        $output = Dom::print(Dom::createComment('Hello'), new PrettyPrintOption());
        static::assertSame("<!--Hello-->", $output);
    }

    public function testPrintDocumentType(): void
    {
        $output = Dom::print(Dom::createDocumentType('html'), new PrettyPrintOption());
        static::assertSame("<!DOCTYPE html>", $output);
    }
}
