<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\CharacterData;
use Manychois\Simdom\Node;

abstract class CharNode extends BaseNode implements CharacterData
{
    use ChildNodeMixin;
    use NonDocumentTypeChildNodeMixin;

    protected string $data;

    public function __construct(string $data)
    {
        parent::__construct();
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

    #endregion

    #region implements CharacterData methods

    public function appendData(string $data): void
    {
        $this->data .= $data;
    }

    #endregion

    #region overrides BaseNode

    public function cloneNode(bool $deep = false): self
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
