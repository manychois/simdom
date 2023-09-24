<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\Dom\NodeFactory;
use Manychois\Simdom\Internal\Parsing\DomParser;

/**
 * Provides methods for creating DOM nodes and parsing HTML.
 */
class Dom
{
    private static ?NodeFactory $nodeFactory = null;

    /**
     * Creates a comment node.
     *
     * @param string $data The comment text.
     *
     * @return CommentInterface The comment node with the specified text.
     */
    public static function createComment(string $data = ''): CommentInterface
    {
        return self::getNodeFactory()->createComment($data);
    }

    /**
     * Creates a empty document node.
     *
     * @return DocumentInterface The empty document node.
     */
    public static function createDocument(): DocumentInterface
    {
        return self::getNodeFactory()->createDocument();
    }

    /**
     * Creates a document fragment node.
     *
     * @return DocumentFragmentInterface The document fragment node.
     */
    public static function createDocumentFragment(): DocumentFragmentInterface
    {
        return self::getNodeFactory()->createDocumentFragment();
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
        return self::getNodeFactory()->createDocumentType($name, $publicId, $systemId);
    }

    /**
     * Creates an element with the specified tag name and namespace.
     *
     * @param string       $tagName   The tag name.
     * @param NamespaceUri $namespace The namespace URI.
     *
     * @return ElementInterface The element node with the specified tag name and namespace.
     */
    public static function createElement(
        string $tagName,
        NamespaceUri $namespace = NamespaceUri::Html
    ): ElementInterface {
        return self::getNodeFactory()->createElement($tagName, $namespace);
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
        return self::getNodeFactory()->createText($data);
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

    /**
     * Gets the node factory.
     *
     * @return NodeFactory The node factory.
     */
    private static function getNodeFactory(): NodeFactory
    {
        if (self::$nodeFactory === null) {
            self::$nodeFactory = new NodeFactory();
        }

        return self::$nodeFactory;
    }
}
