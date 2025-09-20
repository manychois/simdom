<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use InvalidArgumentException;

/**
 * Appends child nodes to a parent node. The child nodes can be provided as:
 *
 * - A string, which will be converted to a Text node.
 * - An AbstractNode instance, which will be appended directly.
 * - A callable that returns any of the accepted types, which will be invoked and its result processed.
 * - An iterable (like an array or Traversable) containing any of the accepted types, which will be processed recursively.
 * - null, which results in no action.
 *
 * @param AbstractParentNode                                $parent     the parent node to which child nodes will be appended
 * @param string|AbstractNode|callable|iterable<mixed>|null $childNodes the child nodes to append
 */
function append(AbstractParentNode $parent, string|AbstractNode|callable|iterable|null $childNodes): void
{
    if (is_string($childNodes)) {
        $parent->append(Text::create($childNodes));
    } elseif ($childNodes instanceof AbstractNode) {
        $parent->append($childNodes);
    } elseif (is_callable($childNodes)) {
        $result = $childNodes($parent);
        if (null === $result) {
            return;
        }
        if (!is_string($result) && !($result instanceof AbstractNode) && !is_callable($result) && !is_iterable($result)) {
            throw new InvalidArgumentException('Child nodes must be string, AbstractNode, callable, iterable, or null.');
        }
        append($parent, $result);
    } elseif (is_iterable($childNodes)) {
        foreach ($childNodes as $child) {
            if (null === $child) {
                continue;
            }
            if (!is_string($child) && !($child instanceof AbstractNode) && !is_callable($child) && !is_iterable($child)) {
                throw new InvalidArgumentException('Child nodes must be string, AbstractNode, callable, iterable, or null.');
            }
            append($parent, $child);
        }
    }
}

/**
 * Creates an Element with the specified name, attributes, and child nodes.
 *
 * @param string                                            $name       the tag name of the element
 * @param string|array<mixed>                               $attributes the attributes for the element; if a string is provided, it is treated as the value of the 'class' attribute
 * @param string|AbstractNode|callable|iterable<mixed>|null $childNodes the child nodes of the element
 *
 * @return Element the created Element
 */
function e(string $name, string|array $attributes = [], string|AbstractNode|callable|iterable|null $childNodes = null): Element
{
    $element = Element::create($name);
    if (is_string($attributes)) {
        $element->setAttr('class', $attributes);
    } else {
        foreach ($attributes as $attrName => $attrValue) {
            if (is_int($attrName)) {
                $attrName = $attrValue;
                if (!is_string($attrName)) {
                    throw new InvalidArgumentException('Attribute name must be a string.');
                }
                $attrValue = '';
            }
            if (!is_string($attrValue)) {
                if (null === $attrValue) {
                    $element->removeAttr($attrName);
                    continue;
                }
                $attrValue = json_encode($attrValue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }
            $element->setAttr($attrName, $attrValue);
        }
    }

    append($element, $childNodes);

    return $element;
}

/**
 * Parses a string of HTML and returns the root Element.
 * If the HTML contains multiple top-level elements, only the first one is returned.
 * If there are no valid elements, an InvalidArgumentException is thrown.
 *
 * @param string $html the HTML string to parse
 *
 * @return Element the root Element parsed from the HTML
 */
function parseElement(string $html): Element
{
    $parser = new HtmlParser();
    $fragment = $parser->parseFragment($html);
    $firstElementChild = $fragment->firstElementChild;
    if (null === $firstElementChild) {
        throw new InvalidArgumentException('The provided HTML does not contain a valid root element.');
    }
    $firstElementChild->remove();

    return $firstElementChild;
}
