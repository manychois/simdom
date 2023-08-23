<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;

/**
 * Represents an element node that contains no child nodes.
 */
class VoidElementNode extends ElementNode
{
    /**
     * Returns true if the given local name is a void element.
     *
     * @param string $localName The local name to check.
     *
     * @return bool True if the given local name is a void element.
     */
    public static function isVoid(string $localName): bool
    {
        return match ($localName) {
            'area',
            'base',
            'br',
            'col',
            'embed',
            'hr',
            'img',
            'input',
            'link',
            'meta',
            'param',
            'source',
            'track',
            'wbr',
            // obsolete
            'basefont',
            'bgsound',
            'command',
            'frame',
            'keygen',
            'isindex',
             => true,
            default => false,
        };
    }

    /**
     * Creates a void element node based on the given element node.
     *
     * @param ElementNode $node The element node to copy.
     */
    public function __construct(ElementNode $node)
    {
        parent::__construct($node->localName());
        $this->attrs = $node->attrs;
    }

    #region extends ElementNode

    /**
     * @inheritdoc
     */
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): void
    {
        if (count($nodes) > 0) {
            $msg = sprintf('Element <%s> cannot have child nodes.', $this->localName());
            throw new InvalidArgumentException($msg);
        }
    }

    /**
     * @inheritdoc
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        // there is no way we have an existing child node
        $msg = sprintf('Element <%s> cannot have child nodes.', $this->localName());
        throw new InvalidArgumentException($msg);
    }

    #endregion
}
