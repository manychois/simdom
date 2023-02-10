<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Node;

class DoctypeNode extends BaseNode implements DocumentType
{
    use ChildNodeMixin;

    private string $name;
    private string $publicId;
    private string $systemId;

    public function __construct(string $name, string $publicId, string $systemId)
    {
        parent::__construct();
        $this->name = $name;
        $this->publicId = $publicId;
        $this->systemId = $systemId;
    }

    #region implements DocumentType properties

    public function name(): string
    {
        return $this->name;
    }

    public function publicId(): string
    {
        return $this->publicId;
    }

    public function systemId(): string
    {
        return $this->systemId;
    }

    #endregion

    #region overrides BaseNode properties

    public function nodeType(): int
    {
        return Node::DOCUMENT_TYPE_NODE;
    }

    public function serialize(): string
    {
        $s = '<!DOCTYPE';
        if ($this->name !== '') {
            $s .= ' ' . $this->name;
        }
        if ($this->publicId === '') {
            if ($this->systemId !== '') {
                $s .= ' SYSTEM "' . $this->systemId . '"';
            }
        } else {
            $s .= ' PUBLIC "' . $this->publicId . '"';
            if ($this->systemId !== '') {
                $s .= ' "' . $this->systemId . '"';
            }
        }
        return $s . '>';
    }

    public function textContent(): ?string
    {
        return null;
    }

    public function textContentSet(string $data): void
    {
        // Do nothing.
    }

    #endregion

    #region overrides BaseNode methods

    public function cloneNode(bool $deep = false): self
    {
        return new static($this->name, $this->publicId, $this->systemId);
    }

    public function isEqualNode(Node $node): bool
    {
        return $node instanceof DocumentType
            && $node->name === $this->name
            && $node->publicId === $this->publicId
            && $node->systemId === $this->systemId;
    }

    #endregion
}
