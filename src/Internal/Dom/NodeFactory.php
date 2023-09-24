<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\CommentInterface;
use Manychois\Simdom\DocumentFragmentInterface;
use Manychois\Simdom\DocumentInterface;
use Manychois\Simdom\DocumentTypeInterface;
use Manychois\Simdom\TextInterface;

/**
 * Factory for creating DOM nodes.
 */
class NodeFactory extends ElementFactory
{
    /**
     * Creates a comment node.
     *
     * @param string $data The comment text.
     *
     * @return CommentInterface The comment node with the specified text.
     */
    public function createComment(string $data = ''): CommentInterface
    {
        return new CommentNode($data);
    }

    /**
     * Creates a empty document node.
     *
     * @return DocumentInterface The empty document node.
     */
    public function createDocument(): DocumentInterface
    {
        return new DocNode();
    }

    /**
     * Creates a document fragment node.
     *
     * @return DocumentFragmentInterface The document fragment node.
     */
    public function createDocumentFragment(): DocumentFragmentInterface
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
    public function createDocumentType(
        string $name = 'html',
        string $publicId = '',
        string $systemId = ''
    ): DocumentTypeInterface {
        return new DoctypeNode($name, $publicId, $systemId);
    }

    /**
     * Creates a text node with the specified data.
     *
     * @param string $data The text data.
     *
     * @return TextInterface The text node with the specified data.
     */
    public function createText(string $data): TextInterface
    {
        return new TextNode($data);
    }
}
