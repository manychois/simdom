<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\CharacterData;
use Manychois\Simdom\Element;
use Manychois\Simdom\Node;

abstract class CharNode extends BaseNode implements CharacterData
{
    use ChildNodeMixin;

    protected string $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    #region implements CharacterData properties

    public function data(): string
    {
        return $this->data;
    }

    public function dataSet(string $data): void
    {
        $this->data = $data;
    }

    public function nextElementSibling(): ?Element
    {
        $nodeList = $this->parent?->nodeList;
        if ($nodeList === null) {
            return null;
        }
        $index = $nodeList->findIndex(fn (Node $node) => $node instanceof Element, $nodeList->indexOf($this) + 1);
        if ($index === -1) {
            return null;
        }
        return $nodeList->item($index);
    }

    public function previousElementSibling(): ?Element
    {
        $nodeList = $this->parent?->nodeList;
        if ($nodeList === null) {
            return null;
        }
        $index = $nodeList->findLastIndex(fn (Node $node) => $node instanceof Element, $nodeList->indexOf($this) - 1);
        if ($index === -1) {
            return null;
        }
        return $nodeList->item($index);
    }

    #endregion

    #region overrides BaseNode

    public function cloneNode(bool $deep = false): static
    {
        return new static($this->data);
    }

    public function isEqualNode(Node $node): bool
    {
        return get_class($node) === get_class($this) && $node->data === $this->data;
    }

    public function textContent(): ?string
    {
        return $this->data;
    }

    public function textContentSet(string $data): void
    {
        $this->data = $data;
    }

    #endregion
}
