<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\DocumentFragmentInterface;
use Manychois\Simdom\NodeInterface;

/**
 * A utility class for flattening nodes into a single array.
 */
class NodeFlattener extends ElementFactory
{
    /**
     * Flattens the specified nodes into a single array.
     *
     * @param string|NodeInterface ...$nodes Nodes to be flattened.
     *                                       String will be converted into Text.
     *                                       DocumentFragment nodes are expanded into their child nodes.
     *
     * @return array<int, AbstractNode> The flattened nodes.
     * Note that they are still connected to their original parents.
     */
    public static function flattenNodes(string|NodeInterface ...$nodes): array
    {
        $flattened = [];
        foreach ($nodes as $node) {
            if (is_string($node)) {
                $flattened[] = new TextNode($node);
            } else {
                if ($node instanceof DocumentFragmentInterface) {
                    foreach ($node->childNodes() as $child) {
                        assert($child instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
                        $index = array_search($child, $flattened, true);
                        if ($index !== false) {
                            array_splice($flattened, $index, 1);
                        }
                        $flattened[] = $child;
                    }
                } else {
                    assert($node instanceof AbstractNode, 'Unexpected implementation of NodeInterface.');
                    $index = array_search($node, $flattened, true);
                    if ($index !== false) {
                        array_splice($flattened, $index, 1);
                    }
                    $flattened[] = $node;
                }
            }
        }

        return $flattened;
    }
}
