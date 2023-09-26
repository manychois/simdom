<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
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
}
