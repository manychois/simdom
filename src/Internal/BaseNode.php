<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\ParentNode;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeType;

abstract class BaseNode implements Node
{
    public static function escapeString(string $text, bool $attrMode = false): string
    {
        $text = str_replace('&', '&amp;', $text);
        $text = mb_ereg_replace('\x{00A0}', '&nbsp;', $text);
        if ($attrMode) {
            $text = str_replace('"', '&quot;', $text);
        } else {
            $text = str_replace(['<', '>'], ['&lt;', '&gt;'], $text);
        }
        return $text;
    }

    public ?BaseParentNode $parent;

    abstract public function cloneNode(bool $deep = false): static;
    abstract public function isEqualNode(Node $node): bool;
    abstract public function nodeType(): NodeType;
    abstract public function serialize(): string;
    abstract public function textContent(): ?string;
    abstract public function textContentSet(string $data): void;

    public function __construct()
    {
        $this->parent = null;
    }


    #region implements Node properties

    public function nextSibling(): ?Node
    {
        if ($this->parent) {
            $index = $this->parent->nodeList->indexOf($this);
            return $this->parent->nodeList->item($index + 1);
        }
        return null;
    }

    public function ownerDocument(): ?Document
    {
        if ($this->parent instanceof Document) {
            return $this->parent;
        } elseif ($this->parent instanceof Element) {
            return $this->parent->ownerDocument();
        }
        return null;
    }

    public function parentElement(): ?Element
    {
        return $this->parent instanceof Element ? $this->parent : null;
    }

    public function parentNode(): ?ParentNode
    {
        return $this->parent;
    }

    public function previousSibling(): ?Node
    {
        if ($this->parent) {
            $index = $this->parent->nodeList->indexOf($this);
            return $this->parent->nodeList->item($index - 1);
        }
        return null;
    }

    #endregion

    #region implements Node methods

    public function getRootNode(): Node
    {
        if ($this->parent) {
            return $this->parent->getRootNode();
        }
        return $this;
    }

    #endregion
}
