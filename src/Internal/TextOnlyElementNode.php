<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

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
        parent::__construct($name);
    }

    #region overrides ElementNode

    /**
     * @param array<\Manychois\Simdom\Node> $nodes
     */
    public function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        parent::validatePreInsertion($child, $nodes);
        foreach ($nodes as $node) {
            if (!$node instanceof Text) {
                throw new PreInsertionException(
                    $this,
                    $node,
                    $child,
                    "Element {$this->localName()} can only contain Text nodes."
                );
            }
        }
    }

    /**
     * @param array<\Manychois\Simdom\Node> $newNodes
     */
    public function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        parent::validatePreReplace($old, $newNodes);
        foreach ($newNodes as $new) {
            if (!$new instanceof Text) {
                throw new PreReplaceException(
                    $this,
                    $new,
                    $old,
                    "Element {$this->localName()} can only contain Text nodes."
                );
            }
        }
    }

    /**
     * @param array<\Manychois\Simdom\Node> $newNodes
     */
    public function validatePreReplaceAll(array $newNodes): void
    {
        parent::validatePreReplaceAll($newNodes);
        foreach ($newNodes as $new) {
            if (!$new instanceof Text) {
                throw new PreReplaceException(
                    $this,
                    $new,
                    null,
                    "Element {$this->localName()} can only contain Text nodes."
                );
            }
        }
    }

    #endregion
}
