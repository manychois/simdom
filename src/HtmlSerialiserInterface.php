<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Serialises DOM nodes to HTML strings.
 */
interface HtmlSerialiserInterface
{
    /**
     * Serialises the given node to an HTML string.
     *
     * @param AbstractNode $node the node to serialise
     *
     * @return string the HTML string representation of the node
     */
    public function serialise(AbstractNode $node): string;

    /**
     * Serialises the given comment node to an HTML string.
     *
     * @param Comment $comment the comment node to serialise
     *
     * @return string the HTML string representation of the comment
     */
    public function serialiseComment(Comment $comment): string;

    /**
     * Serialises the given document type node to an HTML string.
     *
     * @param Doctype $doctype the document type node to serialise
     *
     * @return string the HTML string representation of the document type
     */
    public function serialiseDoctype(Doctype $doctype): string;

    /**
     * Serialises the given document to an HTML string.
     *
     * @param Document $document the document to serialise
     *
     * @return string the HTML string representation of the document
     */
    public function serialiseDocument(Document $document): string;

    /**
     * Serialises the given element node to an HTML string.
     *
     * @param Element $element the element node to serialise
     *
     * @return string the HTML string representation of the element
     */
    public function serialiseElement(Element $element): string;

    /**
     * Serialises the given fragment to an HTML string.
     *
     * @param Fragment $fragment the fragment to serialise
     *
     * @return string the HTML string representation of the fragment
     */
    public function serialiseFragment(Fragment $fragment): string;

    /**
     * Serialises a list of nodes to an HTML string.
     *
     * @param iterable<mixed,AbstractNode> $list the list of nodes to serialise
     *
     * @return string the HTML string representation of the node list
     */
    public function serialiseNodeList(iterable $list): string;

    /**
     * Serialises the opening tag of the given element.
     *
     * @param Element $element the element whose opening tag is to be serialised
     *
     * @return string the HTML string representation of the opening tag
     */
    public function serialiseOpenTag(Element $element): string;

    /**
     * Serialises the given text node to an HTML string.
     *
     * @param Text $text the text node to serialise
     *
     * @return string the HTML string representation of the text
     */
    public function serialiseText(Text $text): string;
}
