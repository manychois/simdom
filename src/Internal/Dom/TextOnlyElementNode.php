<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\TextInterface;

/**
 * Represents an element node that contains only text.
 */
class TextOnlyElementNode extends ElementNode
{
    /**
     * Returns true if the given local name represents a text-only element.
     *
     * @param string $localName The local name to check.
     *
     * @return bool True if the given local name represents a text-only element.
     */
    public static function isTextOnly(string $localName): bool
    {
        return in_array($localName, [
            'noframes',
            'noscript',
            'script',
            'style',
            'template',
            'textarea',
            'title',
            // obsolete
            'noembed',
            'xmp',
        ], true);
    }

    /**
     * Creates a text-only element node based on the given element node.
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
     * @inheritDoc
     */
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): int
    {
        $index = parent::validatePreInsertion($nodes, $ref);

        foreach ($nodes as $node) {
            if (!($node instanceof TextInterface)) {
                $msg = sprintf('Element <%s> can have child text nodes only.', $this->localName());
                throw new InvalidArgumentException($msg);
            }
        }

        return $index;
    }

    /**
     * @inheritDoc
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        $index = parent::validatePreReplace($old, $newNodes);
        foreach ($newNodes as $new) {
            if (!($new instanceof TextInterface)) {
                $msg = sprintf('Element <%s> can have child text nodes only.', $this->localName());
                throw new InvalidArgumentException($msg);
            }
        }

        return $index;
    }

    #endregion
}
