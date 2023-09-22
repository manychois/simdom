<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\NamespaceUri;

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
        $isMatch = preg_match('/^[A-Za-z][^\0\s]*/', $tagName);
        if ($isMatch !== 1) {
            throw new InvalidArgumentException("Invalid tag name: $tagName");
        }

        $ele = new ElementNode($tagName);
        if ($namespace !== NamespaceUri::Html) {
            return new NonHtmlElementNode($ele, $namespace);
        }
        if (TextOnlyElementNode::isTextOnly($ele->localName())) {
            return new TextOnlyElementNode($ele);
        }
        if (VoidElementNode::isVoid($ele->localName())) {
            return new VoidElementNode($ele);
        }

        return $ele;
    }
}
