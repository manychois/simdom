<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Node;

trait ChildNodeMixin
{
    public function after(Node|string ...$nodes): void
    {
        $nodeList = $this->parent?->nodeList;
        if ($nodeList === null) {
            return;
        }
        $i = $nodeList->indexOf($this);
        $viableNextSibling = $nodeList->item($i + 1);
        $nodes = $this->flattenNodes(...$nodes);
        $this->parent->validatePreInsertion($viableNextSibling, $nodes);
        foreach ($nodes as $node) {
            $node->parent?->nodeList?->simRemove($node);
        }
        $nodeList->simInsertAt($i + 1, ...$nodes);
    }

    public function before(Node|string ...$nodes): void
    {
        $nodeList = $this->parent?->nodeList;
        if ($nodeList === null) {
            return;
        }
        $i = $nodeList->indexOf($this);
        $viablePrevSibling = $nodeList->item($i - 1);
        $nodes = $this->flattenNodes(...$nodes);
        $this->parent->validatePreInsertion($viablePrevSibling, $nodes);
        foreach ($nodes as $node) {
            $node->parent?->nodeList?->simRemove($node);
        }
        $nodeList->simInsertAt($i - 1, ...$nodes);
    }

    public function remove(): void
    {
        $this->parent?->nodeList?->simRemove($this);
    }

    public function replaceWith(Node|string ...$nodes): void
    {
        $nodeList = $this->parent?->nodeList;
        if ($nodeList === null) {
            return;
        }
        $i = $nodeList->indexOf($this);
        $nodes = $this->flattenNodes(...$nodes);
        $this->parent->validatePreReplace($this, $nodes);
        $nodeList->simRemoveAt($i);
        foreach ($nodes as $node) {
            $node->parent?->nodeList?->simRemove($node);
        }
        $nodeList->simInsertAt($i, ...$nodes);
    }
}
