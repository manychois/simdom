<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Override;

final class Fragment extends AbstractParentNode
{
    static public function create(): Fragment
    {
        return new Fragment();
    }

    // region extends AbstractParentNode

    #[Override]
    public function clone(bool $deep = true): AbstractNode
    {
        $doc = new Fragment();
        if ($deep) {
            $doc->copyChildNodesFrom($this);
        }

        return $doc;
    }

    // endregion extends AbstractParentNode
}
