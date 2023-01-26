<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

interface LiveNodeListObserver
{
    /**
     * @param array<BaseNode> $nodes
     */
    public function onNodeListAppended(LiveNodeList $nodeList, array $nodes): void;
    public function onNodeListCleared(LiveNodeList $nodeList): void;
    /**
     * @param array<BaseNode> $nodes
     */
    public function onNodeListInserted(LiveNodeList $nodeList, int $index, array $nodes): void;
    public function onNodeListRemoved(LiveNodeList $nodeList, int $index, BaseNode $node): void;
}
