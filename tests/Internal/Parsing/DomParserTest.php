<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\AbstractNode;
use Manychois\Simdom\Internal\Dom\AbstractParentNode;
use Manychois\Simdom\Internal\Dom\CommentNode;
use Manychois\Simdom\Internal\Dom\DocNode;
use Manychois\Simdom\Internal\Dom\DoctypeNode;
use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\Internal\Dom\TextNode;
use Manychois\Simdom\Internal\NamespaceUri;
use Manychois\Simdom\Internal\Parsing\DomParser;
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

    public function testParse(): void
    {
        $parser = new DomParser();
        $files = scandir(__DIR__  . '/test-cases');
        assert($files !== false);
        foreach ($files as $file) {
            if (str_ends_with($file, '.html')) {
                $html = file_get_contents(__DIR__ . '/test-cases/' . $file);
                assert($html !== false);
                $doc = $parser->parse($html);
                $expected = file_get_contents(__DIR__ . '/test-cases/' . str_replace('.html', '.txt', $file));
                $this->assertEquals($expected, static::debugPrint($doc));
            }
        }
    }

    private static function replaceSpecialChars(string $s): string
    {
        $s = str_replace("\n", '↵', $s);
        $s = str_replace("\t", '⇥', $s);

        return $s;
    }
}
