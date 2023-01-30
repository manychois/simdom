<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\AttrNode;
use Manychois\Simdom\Internal\CommentNode;
use Manychois\Simdom\Internal\DocFragNode;
use Manychois\Simdom\Internal\DocNode;
use Manychois\Simdom\Internal\DoctypeNode;
use Manychois\Simdom\Internal\DomPrinter;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Internal\TextNode;
use Manychois\Simdom\Parsing\Parser;

class Dom
{
    public static function createAttr(string $name, string $value): Attr
    {
        $attr = new AttrNode($name);
        $attr->valueSet($value);
        return $attr;
    }

    public static function createComment(string $data): Comment
    {
        return new CommentNode($data);
    }

    public static function createDocument(): Document
    {
        return new DocNode();
    }

    public static function createDocumentFragment(): DocumentFragment
    {
        return new DocFragNode();
    }

    public static function createDocumentType(string $name, string $publicId = '', string $systemId = ''): DocumentType
    {
        return new DoctypeNode($name, $publicId, $systemId);
    }

    public static function createElement(string $name, DomNs $ns = DomNs::Html): Element
    {
        return new ElementNode($name, $ns);
    }

    public static function createParser(): DOMParser
    {
        return new Parser();
    }

    public static function createText(string $data): Text
    {
        return new TextNode($data);
    }

    public static function print(Node $node, ?PrintOption $option = null): string
    {
        $printer = new DomPrinter();
        if ($option === null) {
            $option = new PrintOption();
        }
        return $printer->print($node, $option);
    }
}
