<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Parsing;

use Generator;
use Manychois\Simdom\Internal\Dom\AbstractNode;
use Manychois\Simdom\Internal\Dom\AbstractParentNode;
use Manychois\Simdom\Internal\Dom\CommentNode;
use Manychois\Simdom\Internal\Dom\DocNode;
use Manychois\Simdom\Internal\Dom\DoctypeNode;
use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\Internal\Dom\TextNode;
use Manychois\Simdom\Internal\Parsing\DomParser;
use Manychois\Simdom\NamespaceUri;
use PHPUnit\Framework\TestCase;

class DomParserTest extends TestCase
{
    public static function debugPrint(AbstractNode $node): string
    {
        if ($node instanceof CommentNode) {
            return sprintf('<!--%s-->', self::replaceSpecialChars($node->data())) . "\n";
        }

        if ($node instanceof DoctypeNode) {
            $s = '<!DOCTYPE';
            $s .= sprintf(' name=“%s”', self::replaceSpecialChars($node->name()));
            $s .= sprintf(' publicId=“%s”', self::replaceSpecialChars($node->publicId()));
            $s .= sprintf(' systemId=“%s”', self::replaceSpecialChars($node->systemId()));

            return $s . ">\n";
        }

        if ($node instanceof AbstractParentNode) {
            $temp = '';
            foreach ($node->childNodes() as $child) {
                assert($child instanceof AbstractNode);
                $temp .= static::debugPrint($child);
            }
            $temp = explode("\n", $temp);
            $children = '';
            foreach ($temp as $child) {
                if ($child !== '') {
                    $children .= "\t" . $child . "\n";
                }
            }

            if ($node instanceof DocNode) {
                return "≪Document≫\n" . $children;
            }

            if ($node instanceof ElementNode) {
                $s = '<';
                $s .= match ($node->namespaceUri()) {
                    NamespaceUri::Svg => 'svg:' . $node->localName(),
                    NamespaceUri::MathMl => 'mathml:' . $node->localName(),
                    default => $node->tagName(),
                };
                foreach ($node->attributes() as $name => $value) {
                    if ($value === null) {
                        $s .= sprintf(' %s', $name);
                    } else {
                        $s .= sprintf(' %s=“%s”', $name, self::replaceSpecialChars($value));
                    }
                }

                return "$s>\n$children";
            }
        }

        if ($node instanceof TextNode) {
            return sprintf('“%s”', self::replaceSpecialChars($node->data())) . "\n";
        }

        return '???';
    }

    /**
     * @dataProvider provideTestParse
     */
    public function testParse(string $html, string $expected): void
    {
        $parser = new DomParser();
        $doc = $parser->parse($html);
        static::assertEquals($expected, static::debugPrint($doc));
    }

    public static function provideTestParse(): Generator
    {
        $files = scandir(__DIR__  . '/test-cases');
        assert($files !== false);
        foreach ($files as $file) {
            if (str_ends_with($file, '.html')) {
                $html = file_get_contents(__DIR__ . '/test-cases/' . $file);
                assert($html !== false);
                $expected = file_get_contents(__DIR__ . '/test-cases/' . str_replace('.html', '.txt', $file));
                assert($expected !== false);
                yield $file => [$html, $expected];
            }
        }
    }

    /**
     * @dataProvider provideTestParsePartial
     */
    public function testParsePartial(string $context, string $innerHtml, string $outerHtml): void
    {
        $parser = new DomParser();
        $element = new ElementNode($context);
        $newChildren = $parser->parsePartial($innerHtml, $element);
        foreach ($newChildren as $child) {
            $element->fastAppend($child);
        }
        static::assertEquals($outerHtml, $element->toHtml());
    }

    public static function provideTestParsePartial(): Generator
    {
        yield ['title', '<b> RC data mode</b>', '<title>&lt;b&gt; RC data mode&lt;/b&gt;</title>'];
        yield ['style', 'a>b{color:#000}', '<style>a>b{color:#000}</style>'];
        yield [
            'div',
            '<head><title>ABC</title></head><!-- comment --><body><p>DEF</p></body>',
            '<div><title>ABC</title><!-- comment --><p>DEF</p></div>',
        ];
        yield [
            'html',
            '<head><title>ABC</title></head><!-- comment --><body><p>DEF</p></body>',
            '<html><head><title>ABC</title></head><!-- comment --><body><p>DEF</p></body></html>',
        ];
    }

    /**
     * @dataProvider provideTestTokenizeDoctype
     */
    public function testTokenizeDoctype(string $html, string $name, string $publicId, string $systemId): void
    {
        $parser = new DomParser();
        $doc = $parser->parse($html);
        static::assertCount(2, $doc->childNodes());
        $doctype = $doc->childNodes()->nodeAt(0);

        $html = $doc->childNodes()->nodeAt(-1);
        static::assertInstanceOf(ElementNode::class, $html);
        /** @var ElementNode $html */
        static::assertEquals('HTML', $html->tagName());

        static::assertInstanceOf(DoctypeNode::class, $doctype);
        /** @var DoctypeNode $doctype */
        static::assertEquals($name, $doctype->name());
        static::assertEquals($publicId, $doctype->publicId());
        static::assertEquals($systemId, $doctype->systemId());
    }

    public static function provideTestTokenizeDoctype(): Generator
    {
        yield ['<!DOCTYPE html>', 'html', '', ''];
        yield ['<!doctype', '', '', ''];
        yield [
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
            'html',
            '-//W3C//DTD XHTML 1.1//EN',
            'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd',
        ];
        yield ['<!doctype HTML system "about:legacy-compat">', 'HTML', '', 'about:legacy-compat'];
    }

    private static function replaceSpecialChars(string $s): string
    {
        $s = str_replace("\n", '↵', $s);
        $s = str_replace("\t", '⇥', $s);

        return $s;
    }
}
