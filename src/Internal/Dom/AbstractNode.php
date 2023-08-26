<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use LogicException;
use Manychois\Simdom\ElementInterface;
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
        throw new LogicException('Node is not found in its parent child node list.');
    }

    /**
     * @inheritDoc
     */
    public function nextElement(): ?ElementInterface
    {
        if ($this->pNode === null) {
            return null;
        }

        $fetch = false;
        foreach ($this->pNode->childNodes() as $child) {
            if ($fetch && $child instanceof ElementInterface) {
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
    public function nextNode(): ?NodeInterface
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
    public function parentElement(): ?ElementInterface
    {
        if ($this->pNode === null) {
            return null;
        }

        return $this->pNode instanceof ElementInterface ? $this->pNode : null;
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
    public function prevElement(): ?ElementInterface
    {
        if ($this->pNode === null) {
            return null;
        }

        $prev = null;
        foreach ($this->pNode->childNodes() as $child) {
            if ($child === $this) {
                break;
            }
            if ($child instanceof ElementInterface) {
                $prev = $child;
            }
        }

        return $prev;
    }

    /**
     * @inheritDoc
     */
    public function prevNode(): ?NodeInterface
    {
        if ($this->pNode === null) {
            return null;
        }

        $prev = null;
        foreach ($this->pNode->childNodes() as $child) {
            if ($child === $this) {
                break;
            }
            $prev = $child;
        }

        return $prev;
    }

    /**
     * @inheritDoc
     */
    public function rootNode(): ?ParentNodeInterface
    {
        if ($this->pNode === null) {
            return null;
        }

        $node = $this->pNode;
        while ($node->parentNode() !== null) {
            $node = $node->parentNode();
        }

        return $node;
    }

    #endregion
}
