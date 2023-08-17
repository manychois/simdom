<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\Internal\Dom\ParentNodeInterface;
use Manychois\Simdom\NodeInterface;

/**
 * Internal implementation of NodeInterface.
 */
abstract class AbstractNode implements NodeInterface
{
    protected ?AbstractParentNode $pNode = null;

    #region implements NodeInterface

    /**
     * @inheritDoc
     */
    public function parentNode(): ?ParentNodeInterface
    {
        return $this->pNode;
    }

    #endregion
}
