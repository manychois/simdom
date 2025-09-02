<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;
use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Doctype;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Fragment;
use Manychois\Simdom\Text;

/**
 * Provides utility functions for node manipulation.
 */
class NodeUtility
{
    /**
     * Converts a list of nodes or strings into an array of distinct nodes.
     *
     * @return array<int,Comment|Doctype|Element|Text> the array of distinct nodes
     */
    public static function convertToDistinctNodes(string|AbstractNode ...$nodes): array
    {
        $converted = [];
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                throw new InvalidArgumentException('Document node is not allowed');
            }
            if ($node instanceof Fragment) {
                $children = $node->childNodes->asArray();
                $node->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Clear();
                $converted = array_merge($converted, $children);
                continue;
            }

            if (is_string($node)) {
                $converted[] = Text::𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create($node);
            } else {
                assert(
                    $node instanceof Comment
                    || $node instanceof Doctype
                    || $node instanceof Element
                    || $node instanceof Text,
                    'Node must be an instance of Comment, Doctype, Element, or Text'
                );
                $node->remove();
                $i = array_search($node, $converted, true);
                if (is_int($i)) {
                    array_splice($converted, $i, 1);
                }
                $converted[] = $node;
            }
        }

        return $converted;
    }
}
