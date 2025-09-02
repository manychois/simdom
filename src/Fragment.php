<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Override;

/**
 * Represents a document fragment in the DOM.
 */
final class Fragment extends AbstractParentNode
{
    /**
     * Creates a new Fragment node.
     *
     * @return Fragment the created Fragment node
     */
    public static function create(): Fragment
    {
        return new Fragment();
    }

    // region extends AbstractParentNode

    #[Override]
    public function clone(bool $deep = true): Fragment
    {
        $doc = new Fragment();
        if ($deep) {
            $doc->copyChildNodesFrom($this);
        }

        return $doc;
    }

    // endregion extends AbstractParentNode
}
