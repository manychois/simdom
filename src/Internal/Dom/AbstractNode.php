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
    public function index(): int
    {
        if ($this->pNode === null) {
            return -1;
        }
        foreach ($this->pNode->childNodes() as $i => $child) {
            if ($child === $this) {
                assert($i >= 0, "Invalid index $i");

                return $i;
            }
        }

        return -1;
    }

    /**
     * @inheritDoc
     */
    public function nextSibling(): ?NodeInterface
    {
        if ($this->pNode === null) {
            return null;
        }

        $fetch = false;
        foreach ($this->pNode->childNodes() as $child) {
            if ($fetch) {
                return $child;
            }

            if ($child === $this) {
                $fetch = true;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function parentNode(): ?ParentNodeInterface
    {
        return $this->pNode;
    }

    /**
     * @inheritDoc
     */
    public function previousSibling(): ?NodeInterface
    {
        if ($this->pNode === null) {
            return null;
        }

        $prev = null;
        foreach ($this->pNode->childNodes() as $child) {
            if ($child === $this) {
                return $prev;
            }
            $prev = $child;
        }

        return $prev;
    }

    #endregion
}
