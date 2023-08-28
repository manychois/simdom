<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use InvalidArgumentException;
use Manychois\Simdom\Internal\Dom\CommentNode;
use Manychois\Simdom\Internal\Dom\DocFragmentNode;
use Manychois\Simdom\Internal\Dom\DocNode;
use Manychois\Simdom\Internal\Dom\DoctypeNode;
use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\Internal\Dom\NonHtmlElementNode;
use Manychois\Simdom\Internal\Dom\TextNode;
use Manychois\Simdom\Internal\Dom\TextOnlyElementNode;
use Manychois\Simdom\Internal\Dom\VoidElementNode;
use Manychois\Simdom\Internal\Parsing\DomParser;

/**
 * Provides methods for creating DOM nodes and parsing HTML.
 */
class Dom
{
    /**
     * Creates a comment node.
     *
     * @param string $data The comment text.
     *
     * @return CommentInterface The comment node with the specified text.
     */
    public static function createComment(string $data = ''): CommentInterface
    {
        return new CommentNode($data);
    }

    /**
     * Creates a empty document node.
     *
     * @return DocumentInterface The empty document node.
     */
    public static function createDocument(): DocumentInterface
    {
        return new DocNode();
    }

    /**
     * Creates a document fragment node.
     *
     * @return DocumentFragmentInterface The document fragment node.
     */
    public static function createDocumentFragment(): DocumentFragmentInterface
    {
        return new DocFragmentNode();
    }

    /**
     * Creates a document type node with the specified name, public ID, and system ID.
     * By default it creates a HTML5 document type node.
     *
     * @param string $name     The name of the document type.
     * @param string $publicId The public ID of the document type.
     * @param string $systemId The system ID of the document type.
     *
     * @return DocumentTypeInterface The document type node with the specified name, public ID, and system ID.
     */
    public static function createDocumentType(
        string $name = 'html',
        string $publicId = '',
        string $systemId = ''
    ): DocumentTypeInterface {
        return new DoctypeNode($name, $publicId, $systemId);
    }

    /**
     * Creates an element with the specified tag name and namespace.
     *
     * @param string       $tagName The tag name.
     * @param NamespaceUri $ns      The namespace URI.
     *
     * @return ElementInterface The element node with the specified tag name and namespace.
     */
    public static function createElement(string $tagName, NamespaceUri $ns = NamespaceUri::Html): ElementInterface
    {
        if ($tagName === '') {
            throw new InvalidArgumentException('Tag name cannot be empty.');
        }
        $isMatch = preg_match('/^[A-Za-z][^\0\s]*/', $tagName);
        if ($isMatch !== 1) {
            throw new InvalidArgumentException("Invalid tag name: $tagName");
        }

        $e = new ElementNode($tagName);
        if ($ns !== NamespaceUri::Html) {
            return new NonHtmlElementNode($e, $ns);
        }
        if (TextOnlyElementNode::isTextOnly($e->localName())) {
            return new TextOnlyElementNode($e);
        }
        if (VoidElementNode::isVoid($e->localName())) {
            return new VoidElementNode($e);
        }

        return $e;
    }

    /**
     * Creates a text node with the specified data.
     *
     * @param string $data The text data.
     *
     * @return TextInterface The text node with the specified data.
     */
    public static function createText(string $data): TextInterface
    {
        return new TextNode($data);
    }

    /**
     * Parses the specified HTML and returns a document node.
     *
     * @param string $html The HTML to parse.
     *
     * @return DocumentInterface The document node constructed from the specified HTML.
     */
    public static function parse(string $html): DocumentInterface
    {
        $parser = new DomParser();

        return $parser->parse($html);
    }
}
