<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\AttrNode;
use Manychois\Simdom\Internal\BaseNode;
use Manychois\Simdom\Internal\CommentNode;
use Manychois\Simdom\Internal\DocFragNode;
use Manychois\Simdom\Internal\DocNode;
use Manychois\Simdom\Internal\DoctypeNode;
use Manychois\Simdom\Internal\DomPrinter;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Internal\TextNode;
use Manychois\Simdom\Internal\TextOnlyElementNode;
use Manychois\Simdom\Parsing\LegacyParser;
use Manychois\Simdom\Parsing\Parser;

/**
 * Provides a set of static methods for creating and printing DOM objects.
 */
class Dom
{
    /**
     * Creates a new `Attr` node.
     * @param string $localName The local part of the qualified name of the attribute.
     * @param string $value The attribute's value.
     */
    public static function createAttr(string $localName, string $value): Attr
    {
        $attr = new AttrNode($localName);
        $attr->valueSet($value);
        return $attr;
    }

    /**
     * Creates a new `Comment` node.
     * @param string $data The textual data of the comment.
     */
    public static function createComment(string $data): Comment
    {
        return new CommentNode($data);
    }

    /**
     * Creates a new `Document` node.
     */
    public static function createDocument(): Document
    {
        return new DocNode();
    }

    /**
     * Creates a new `DocumentFragment` node.
     */
    public static function createDocumentFragment(): DocumentFragment
    {
        return new DocFragNode();
    }

    /**
     * Creates a new `DocumentType` node.
     * @param string $name The name of the document type.
     * @param string $publicId The public identifier of the document type.
     * @param string $systemId The system identifier of the document type.
     */
    public static function createDocumentType(string $name, string $publicId = '', string $systemId = ''): DocumentType
    {
        return new DoctypeNode($name, $publicId, $systemId);
    }

    /**
     * Creates a new `Element` node.
     * @param string $localName The local part of the qualified name of the element.
     * @param DomNs $ns The namespace URI of the element.
     * @param string|array<Node> ...$inner The nodes to append to the newly created element.
     */
    public static function createElement(string $localName, string $ns = DomNs::HTML, ...$inner): Element
    {
        $element = null;
        if ($ns === DomNs::HTML) {
            $localName = strtolower($localName);
            if (TextOnlyElementNode::match($localName)) {
                $element = new TextOnlyElementNode($localName, $ns, true);
            }
        }
        if ($element === null) {
            $element = new ElementNode($localName, $ns);
        }
        $element->append(...$inner);
        return $element;
    }

    /**
     * Creates a new `DOMParser` object.
     * @param bool $legacyMode Whether to use the native PHP dom extension for parsing.
     */
    public static function createParser(bool $legacyMode = false): DOMParser
    {
        return $legacyMode ? new LegacyParser() : new Parser();
    }

    /**
     * Creates a new `Text` node.
     * @param string $data The data of the text node.
     */
    public static function createText(string $data): Text
    {
        return new TextNode($data);
    }

    /**
     * Returns the string representation of the given DOM node.
     * @param Node $node The DOM node to be printed.
     * @param PrettyPrintOption|null $option The pretty print option. If `null`, the output will be the same as
     *                                       outerHTML.
     */
    public static function print(Node $node, ?PrettyPrintOption $option = null): string
    {
        if ($option === null) {
            assert($node instanceof BaseNode);
            return $node->serialize();
        }
        $printer = new DomPrinter($option);
        return $printer->print($node);
    }
}
