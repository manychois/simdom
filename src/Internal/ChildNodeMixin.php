<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

trait ChildNodeMixin
{
    /**
     * @param Node|string ...$nodes A list of nodes or strings to be inserted.
     */
    public function after(...$nodes): void
    {
        $nodeList = $this->parent ? $this->parent->nodeList : null;
        if ($nodeList === null) {
            return;
        }
        $i = $nodeList->indexOf($this);
        $viableNextSibling = $nodeList->item($i + 1);
        $nodes = static::flattenNodes(...$nodes);
        $this->parent->validatePreInsertion($viableNextSibling, $nodes);
        foreach ($nodes as $node) {
            if ($node->parent) {
                $node->parent->nodeList->simRemove($node);
            }
        }
        $nodeList->simInsertAt($i + 1, ...$nodes);
    }

    /**
     * @param Node|string ...$nodes A list of nodes or strings to be inserted.
     */
    public function before(...$nodes): void
    {
        $nodeList = $this->parent ? $this->parent->nodeList : null;
        if ($nodeList === null) {
            return;
        }
        $i = $nodeList->indexOf($this);
        $viablePrevSibling = $nodeList->item($i - 1);
        $nodes = static::flattenNodes(...$nodes);
        $this->parent->validatePreInsertion($viablePrevSibling, $nodes);
        foreach ($nodes as $node) {
            if ($node->parent) {
                $node->parent->nodeList->simRemove($node);
            }
        }
        $nodeList->simInsertAt($i - 1, ...$nodes);
    }

    public function remove(): void
    {
        if ($this->parent) {
            $this->parent->nodeList->simRemove($this);
        }
    }

    /**
     * @param Node|string ...$nodes
     */
    public function replaceWith(...$nodes): void
    {
        $nodeList = $this->parent ? $this->parent->nodeList : null;
        if ($nodeList === null) {
            return;
        }
        $i = $nodeList->indexOf($this);
        $nodes = static::flattenNodes(...$nodes);
        $this->parent->validatePreReplace($this, $nodes);
        $nodeList->simRemoveAt($i);
        foreach ($nodes as $node) {
            if ($node->parent) {
                $node->parent->nodeList->simRemove($node);
            }
        }
        $nodeList->simInsertAt($i, ...$nodes);
    }
}
