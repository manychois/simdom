<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\DomNs;
use Manychois\Simdom\Element;
use Manychois\Simdom\NodeType;
use Manychois\Simdom\Text;

class TextNode extends CharNode implements Text
{
    #region overrides BaseNode properties

    public function nodeType(): NodeType
    {
        return NodeType::Text;
    }

    public function serialize(): string
    {
        $parent = $this->parent;
        if ($parent instanceof Element && $parent->namespaceURI() === DomNs::Html) {
            if (
                in_array($parent->localName(), [
                'style', 'script', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript', 'template',
                ], true)
            ) {
                return $this->data;
            }
        }
        return static::escapeString($this->data);
    }

    #endregion
}
