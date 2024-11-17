<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\UnitTests\Parsing;

use Manychois\Simdom\Comment;
use Manychois\Simdom\Element;
use Manychois\Simdom\Parsing\DomParser;
use Manychois\Simdom\Text;
use PHPUnit\Framework\TestCase;

class DomParserTest extends TestCase
{
    public function testParseDocumentOnSimpleDoc(): void
    {
        $parser = new DomParser();
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
        </head>
        <body>
            <h1>Hello, World!</h1>
        </body>
        </html>
        HTML;
        $doc = $parser->parseDocument($html);
        static::assertSame($html, $doc->toHtml());
    }

    public function testPartialOnVoidElement(): void
    {
        $parser = new DomParser();
        $html = '<!-- it will not go into the DOM -->';
        $parsed = $parser->parsePartial($html, 'img');
        static::assertCount(0, $parsed);
    }

    public function testPartialOnRawTestElement(): void
    {
        $parser = new DomParser();
        $html = 'console.log("A &amp; B");';
        $parsed = $parser->parsePartial($html, 'script');
        static::assertCount(1, $parsed);
        $node = $parsed[0];
        static::assertTrue($node instanceof Text);
        \assert($node instanceof Text);
        static::assertSame($html, $node->data);
    }

    public function testPartialOnEscapableRawTextElement(): void
    {
        $parser = new DomParser();
        $html = 'console.log("A &amp; B");';
        $parsed = $parser->parsePartial($html, 'textarea');
        static::assertCount(1, $parsed);
        $node = $parsed[0];
        static::assertTrue($node instanceof Text);
        \assert($node instanceof Text);
        static::assertSame('console.log("A & B");', $node->data);
    }

    public function testPartialOnNormalElement(): void
    {
        $parser = new DomParser();
        $html = 'A &amp; B<p>Hello, World!</p><!-- comment -->';
        $parsed = $parser->parsePartial($html, 'div');
        static::assertCount(3, $parsed);
        $node = $parsed[0];
        static::assertTrue($node instanceof Text);
        \assert($node instanceof Text);
        static::assertSame('A & B', $node->data);
        $node = $parsed[1];
        static::assertTrue($node instanceof Element);
        \assert($node instanceof Element);
        static::assertSame('<p>Hello, World!</p>', $node->toHtml());
        $node = $parsed[2];
        static::assertTrue($node instanceof Comment);
        \assert($node instanceof Comment);
        static::assertSame(' comment ', $node->data);
    }

    public function testPartialOnGibberish(): void
    {
        $parser = new DomParser();
        $html = 'abc';
        $parsed = $parser->parsePartial($html, '123');
        static::assertCount(0, $parsed);
    }
}
