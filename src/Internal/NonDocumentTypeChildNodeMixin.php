<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Element;
use Manychois\Simdom\Node;

trait NonDocumentTypeChildNodeMixin
{
    public function nextElementSibling(): ?Element
    {
        $nodeList = $this->parent?->nodeList;
        if ($nodeList === null) {
            return null;
        }
        $index = $nodeList->findIndex(fn (Node $node) => $node instanceof Element, $nodeList->indexOf($this) + 1);
        if ($index === -1) {
            return null;
        }
        return $nodeList->item($index);
    }

    public function previousElementSibling(): ?Element
    {
        $nodeList = $this->parent?->nodeList;
        if ($nodeList === null) {
            return null;
        }
        $i = $nodeList->indexOf($this) - 1;
        if ($i < 0) {
            return null;
        }
        $index = $nodeList->findLastIndex(fn (Node $node) => $node instanceof Element, $i);
        if ($index === -1) {
            return null;
        }
        return $nodeList->item($index);
    }
}
