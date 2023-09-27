<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\Parsing\StartTagToken;
use Manychois\Simdom\NamespaceUri;

/**
 * Factory for creating element nodes.
 */
class ElementFactory
{
    /**
     * Creates an element with the specified tag name and namespace.
     *
     * @param string       $tagName   The tag name.
     * @param NamespaceUri $namespace The namespace URI.
     *
     * @return ElementInterface The element node with the specified tag name and namespace.
     */
    public function createElement(string $tagName, NamespaceUri $namespace = NamespaceUri::Html): ElementInterface
    {
        if ($tagName === '') {
            throw new InvalidArgumentException('Tag name cannot be empty.');
        }

        $tagName = strtolower($tagName);
        $isMatch = preg_match('/^[a-z][^\0\s]*/', $tagName);
        if ($isMatch !== 1) {
            throw new InvalidArgumentException("Invalid tag name: $tagName");
        }

        if ($namespace === NamespaceUri::Html) {
            if (TextOnlyElementNode::isTextOnly($tagName)) {
                return new TextOnlyElementNode($tagName);
            }

            if (VoidElementNode::isVoid($tagName)) {
                return new VoidElementNode($tagName);
            }

            return new ElementNode($tagName);
        }

        return new NonHtmlElementNode($tagName, $namespace);
    }

    /**
     * Converts the element node of start tag token into specific subclass of ElementNode, if applicable.
     *
     * @param StartTagToken $token       The start tag token.
     * @param NamespaceUri  $namespace   The target namespace URI.
     * @param bool          $pushToStack Whether to push the element node to the stack.
     *
     * @return ElementNode The converted element node.
     */
    public function convertSpecific(StartTagToken $token, NamespaceUri $namespace, bool &$pushToStack): ElementNode
    {
        $element = $token->node;
        $finalEle = $element;
        $localName = $element->localName();
        if ($namespace === NamespaceUri::Html) {
            if (VoidElementNode::isVoid($localName)) {
                $finalEle = new VoidElementNode($localName);
                $pushToStack = false;
            } elseif (TextOnlyElementNode::isTextOnly($localName)) {
                $finalEle = new TextOnlyElementNode($localName);
                $pushToStack = false;
            }
        } else {
            $pushToStack = !$token->selfClosing;
            $finalEle = new NonHtmlElementNode($localName, $namespace);
        }

        if ($finalEle !== $element) {
            foreach ($element->attributes() as $k => $v) {
                $finalEle->setAttribute($k, $v);
            }
        }

        return $finalEle;
    }
}
