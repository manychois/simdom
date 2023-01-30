<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use IteratorAggregate;
use Manychois\Simdom\Element;
use Manychois\Simdom\HTMLCollection;
use Traversable;

class ChildElementList implements HTMLCollection, IteratorAggregate, LiveNodeListObserver
{
    private readonly LiveNodeList $nodeList;
    /**
     * @var array<int>
     */
    private array $lookup;

    public function __construct(BaseParentNode $owner)
    {
        $this->nodeList = $owner->nodeList;
        $this->nodeList->observer = $this;
        $this->lookup = [];
        foreach ($this->nodeList as $i => $node) {
            if ($node instanceof Element) {
                $this->lookup[] = $i;
            }
        }
    }

    #region implements HTMLCollection

    public function item(int $index): ?ElementNode
    {
        $nodeIndex = $this->lookup[$index] ?? -1;
        return $this->nodeList->item($nodeIndex);
    }

    public function length(): int
    {
        return count($this->lookup);
    }

    #endregion

    #region implemnts IteratorAggregate

    /**
     * @return Traversable<Element>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->lookup as $i) {
            yield $this->nodeList->item($i);
        }
    }

    #endregion

    #region implements NodeListObserver

    /**
     * @param array<BaseNode> $nodes
     */
    public function onNodeListAppended(LiveNodeList $nodeList, array $nodes): void
    {
        $i = $nodeList->length() - count($nodes);
        foreach ($nodes as $node) {
            if ($node instanceof Element) {
                $this->lookup[] = $i;
            }
            $i++;
        }
    }

    public function onNodeListCleared(LiveNodeList $nodeList): void
    {
        $this->lookup = [];
    }

    /**
     * @param array<BaseNode> $nodes
     */
    public function onNodeListInserted(LiveNodeList $nodeList, int $index, array $nodes): void
    {
        $newLookup = [];
        $injected = false;
        $addedNodeCount = count($nodes);
        foreach ($this->lookup as $nodeIndex) {
            if ($nodeIndex < $index) {
                $newLookup[] = $nodeIndex;
            } else {
                if (!$injected) {
                    foreach ($nodes as $i => $node) {
                        if ($node instanceof Element) {
                            $newLookup[] = $index + $i;
                        }
                    }
                    $injected = true;
                }
                $newLookup[] = $nodeIndex + $addedNodeCount;
            }
        }
        if (!$injected) {
            foreach ($nodes as $i => $node) {
                if ($node instanceof Element) {
                    $newLookup[] = $index + $i;
                }
            }
        }
        $this->lookup = $newLookup;
    }

    public function onNodeListRemoved(LiveNodeList $nodeList, int $index, BaseNode $node): void
    {
        $newLookup = [];
        foreach ($this->lookup as $nodeIndex) {
            if ($nodeIndex < $index) {
                $newLookup[] = $nodeIndex;
            } elseif ($nodeIndex === $index) {
                // skipped
            } else {
                $newLookup[] = $nodeIndex - 1;
            }
        }
        $this->lookup = $newLookup;
    }

    #endregion
}
