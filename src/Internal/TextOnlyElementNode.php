<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\DomNs;
use Manychois\Simdom\Node;
use Manychois\Simdom\Text;

class TextOnlyElementNode extends ElementNode
{
    public static function match(string $localName): bool
    {
        return in_array($localName, [
            'noscript',
            'script',
            'style',
            'template',
            'title',
        ], true);
    }

    public function __construct(string $name)
    {
        parent::__construct($name, DomNs::Html);
    }

    #region overrides ElementNode

    /**
     * @param array<Node> $nodes
     */
    public function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        parent::validatePreInsertion($child, $nodes);
        $getEx = fn (Node $node, string $msg) => new PreInsertionException($this, $node, $child, $msg);
        foreach ($nodes as $node) {
            if (!$node instanceof Text) {
                throw $getEx($node, "Element {$this->localName()} can only contain Text nodes.");
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        parent::validatePreReplace($old, $newNodes);
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, $old, $msg);
        foreach ($newNodes as $new) {
            if (!$new instanceof Text) {
                throw $getEx($new, "Element {$this->localName()} can only contain Text nodes.");
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplaceAll(array $newNodes): void
    {
        parent::validatePreReplaceAll($newNodes);
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, null, $msg);
        foreach ($newNodes as $new) {
            if (!$new instanceof Text) {
                throw $getEx($new, "Element {$this->localName()} can only contain Text nodes.");
            }
        }
    }

    #endregion
}
