<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use DOMDocument;
use InvalidArgumentException;
use Manychois\Simdom\Dom;
use Manychois\Simdom\DomNodeConverter;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Element;
use PHPUnit\Framework\TestCase;

class DomNodeConverterTest extends TestCase
{
    public function testConvertComment(): void
    {
        $doc = new DOMDocument();
        $comment = $doc->createComment('test');
        $doc->appendChild($comment);
        $converter = new DomNodeConverter();
        $converted = $converter->convertToComment($comment, true);
        $output = Dom::print($converted->ownerDocument());
        static::assertSame('<!--test-->', $output);
    }

    public function testConvertText(): void
    {
        $doc = new DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);

        $ele = $doc->createElement('div');
        $attr = $doc->createAttributeNS('https://unit.test', 'foo:bar');
        $attr->value = 'baz';
        $ele->setAttributeNode($attr);
        $text = $doc->createTextNode('test');
        $ele->appendChild($text);
        $converter = new DomNodeConverter();
        $converted = $converter->convertToText($text, true);
        $root = $converted->getRootNode();
        assert($root instanceof Element);
        static::assertSame('<div foo:bar="baz">test</div>', $root->outerHTML());
    }

    public function testConvertElement(): void
    {
        $doc = new DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $body = $doc->createElement('body');
        $html->appendChild($body);
        $main = $doc->createElement('main');
        $body->appendChild($main);

        $converter = new DomNodeConverter();
        $converted = $converter->convertToElement($main, true);
        $output = Dom::print($converted->ownerDocument());
        static::assertSame('<html><body><main></main></body></html>', $output);
    }

    public function testConvertDocumentFragment(): void
    {
        $doc = new DOMDocument();
        $frag = $doc->createDocumentFragment();
        $ele = $doc->createElement('div');
        $frag->appendChild($ele);

        $converter = new DomNodeConverter();
        $converted = $converter->convertToDocumentFragment($frag);
        $output = Dom::print($converted);
        static::assertSame('<div></div>', $output);
    }

    public function testConvertDocumentType(): void
    {
        $doc = new DOMDocument();
        $doc->loadHTML('<html></html>');
        $doctype = $doc->doctype;
        $converter = new DomNodeConverter();
        $converted = $converter->convertToDocumentType($doctype);
        $output = Dom::print($converted);
        $expected = implode('', [
            '<!DOCTYPE html PUBLIC',
            ' "-//W3C//DTD HTML 4.0 Transitional//EN"',
            ' "http://www.w3.org/TR/REC-html40/loose.dtd">',
        ]);
        static::assertSame($expected, $output);
    }

    public function testConvertingUnsupportedDomNodeExpectsEx(): void
    {
        $expectedEx = new InvalidArgumentException('Unsupported node type: 4, node name: #cdata-section');
        $this->expectExceptionObject($expectedEx);
        $doc = new DOMDocument();
        $cdata = $doc->createCDATASection('test');
        $converter = new DomNodeConverter();
        $converter->convertToNode($cdata);
    }

    public function testImportText(): void
    {
        $doc = new DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $body = $doc->createElement('body');
        $html->appendChild($body);

        $text = Dom::createText('test');
        $converter = new DomNodeConverter();
        $imported = $converter->importText($text, $doc);
        $body->appendChild($imported);
        static::assertSame('<body>test</body>', $doc->saveHTML($body));
    }

    public function testImportComment(): void
    {
        $doc = new DOMDocument();
        $comment = Dom::createComment('test');
        $converter = new DomNodeConverter();
        $imported = $converter->importComment($comment, $doc);
        $doc->appendChild($imported);
        static::assertSame("<!--test-->\n", $doc->saveHTML());
    }

    public function testImportDocumentType(): void
    {
        $doc = new DOMDocument();
        $doctype = Dom::createDocumentType('html', 'abc', 'def');
        $converter = new DomNodeConverter();
        $imported = $converter->importDocumentType($doctype, $doc);
        $doc->appendChild($imported);
        static::assertSame('<!DOCTYPE html PUBLIC "abc" "def">', $doc->saveXML($imported));
    }

    public function testImportDocumentFragment(): void
    {
        $doc = new DOMDocument();
        $frag = Dom::createDocumentFragment();
        $html = Dom::createElement('html');
        $frag->appendChild($html);
        $head = Dom::createElement('head');
        $body = Dom::createElement('body');
        $html->append($head, $body);

        $converter = new DomNodeConverter();
        $imported = $converter->importDocumentFragment($frag, $doc);
        $doc->appendChild($imported);
        static::assertSame(
            '<html><head></head><body></body></html>' . "\n",
            $doc->saveHTML()
        );
    }

    public function testImportElement(): void
    {
        $doc = new DOMDocument();
        $html = $doc->createElement('html');
        $body = $doc->createElement('body');
        $doc->appendChild($html);
        $html->appendChild($body);
        static::assertSame("<html><body></body></html>\n", $doc->saveHTML());

        $converter = new DomNodeConverter();
        $p = Dom::createElement('p');
        $p->setAttribute('class', 'a');
        $p->append('abc');
        $imported = $converter->importElement($p, $doc);
        $body->appendChild($imported);
        static::assertSame(
            '<html><body><p class="a">abc</p></body></html>' . "\n",
            $doc->saveHTML()
        );

        $svg = Dom::createElement('svg', DomNs::SVG);
        $circle = Dom::createElement('circle', DomNs::SVG);
        $circle->setAttribute('cx', '1');
        $circle->setAttribute('cy', '2');
        $circle->setAttribute('r', '3');
        $svg->append($circle);
        $imported = $converter->importElement($svg, $doc);
        $body->appendChild($imported);
        $expected = implode('', [
            '<html>',
            '<body>',
            '<p class="a">abc</p>',
            '<svg xmlns="http://www.w3.org/2000/svg">',
            '<circle cx="1" cy="2" r="3"></circle>',
            '</svg>',
            '</body>',
            '</html>',
        ]) . "\n";
        static::assertSame($expected, $doc->saveHTML());
    }

    public function testImportDocumentExpectsEx(): void
    {
        $expectedEx = new InvalidArgumentException('Unsupported node class: Manychois\Simdom\Internal\DocNode');
        $this->expectExceptionObject($expectedEx);
        $doc = new DOMDocument();
        $converter = new DomNodeConverter();
        $simdomDoc = Dom::createDocument();
        $converter->importNode($simdomDoc, $doc);
    }

    public function testConvertToDOMDocument(): void
    {
        $doc = Dom::createDocument();
        $doc->append(
            Dom::createElement(
                'html',
                DomNs::HTML,
                Dom::createElement(
                    'head',
                    DomNs::HTML,
                    Dom::createElement('title', DomNs::HTML, 'test'),
                ),
                Dom::createElement(
                    'body',
                    DomNs::HTML,
                    Dom::createElement('p', DomNs::HTML, 'abc'),
                ),
            )
        );
        $converter = new DomNodeConverter();
        $domDoc = $converter->convertToDOMDocument($doc);
        $expected = implode('', [
            '<html>',
            '<head>',
            '<title>test</title>',
            '</head>',
            '<body>',
            '<p>abc</p>',
            '</body>',
            '</html>',
        ]) . "\n";
        static::assertSame($expected, $domDoc->saveHTML());
    }
}
