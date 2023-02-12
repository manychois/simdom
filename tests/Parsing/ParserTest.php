<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Parsing;

use Manychois\Simdom\Dom;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Parsing\Parser;
use Manychois\SimdomTests\DomDebugPrinter;
use PHPUnit\Framework\TestCase;
use Traversable;

class ParserTest extends TestCase
{
    private static function getSampleDir(string $funcName): string
    {
        $dirName = preg_replace('/^test/', '', $funcName);
        $dirName = strtolower($dirName[0]) . substr($dirName, 1);
        return __DIR__ . '/parser-test-samples/' . $dirName;
    }

    private static function runParseCases(string $dirname): void
    {
        $parser = Dom::createParser();
        $printer = new DomDebugPrinter();
        foreach (scandir($dirname) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $pathInfo = pathinfo($file);
            if ($pathInfo['extension'] !== 'html') {
                continue;
            }
            $html = file_get_contents("$dirname/$file");
            $expected = file_get_contents("$dirname/{$pathInfo['filename']}.txt");
            $document = $parser->parseFromString($html);
            $debug = $printer->print($document);
            static::assertEquals($expected, $debug, "Unexpected parse result for $file");
        }
    }

    public function testRunInitialInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    public function testRunBeforeHtmlInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    public function testRunBeforeHeadInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    public function testRunInHeadInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    public function testRunAfterHeadInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    public function testRunInBodyInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    public function testRunAfterBodyInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    public function testRunAfterAfterBodyInsertionMode(): void
    {
        static::runParseCases($this->getSampleDir(__FUNCTION__));
    }

    #region parsePartial tests

    /**
     * @dataProvider parsePartialProvider
     */
    public function testParsePartial(ElementNode $context, string $html, string $expects): void
    {
        $parser = new Parser();
        $nodeList = $parser->parsePartial($context, $html);
        /** @var ElementNode $root */
        $root = $nodeList->owner;
        static::assertEquals($expects, $root->innerHTML());
    }

    public static function parsePartialProvider(): Traversable
    {
        yield [
            new ElementNode('div'),
            '<p>A & B</p>',
            '<p>A &amp; B</p>',
        ];
        yield [
            new ElementNode('title'),
            '<p>A & B</p>',
            '&lt;p&gt;A &amp; B&lt;/p&gt;',
        ];
        yield [
            new ElementNode('script'),
            '<p>A & B</p>',
            '&lt;p&gt;A &amp; B&lt;/p&gt;',
        ];
        yield [
            new ElementNode('svg', DomNs::SVG),
            '<p>A & B</p>',
            '<p>A &amp; B</p>',
        ];
    }

    #endregion
}
