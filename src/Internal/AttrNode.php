<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Attr;

class AttrNode implements Attr
{
    public static function isBoolean(string $name): bool
    {
        return in_array($name, [
            'allowfullscreen',
            'async',
            'autofocus',
            'autoplay',
            'checked',
            'controls',
            'default',
            'defer',
            'disabled',
            'formnovalidate',
            'inert',
            'ismap',
            'itemscope',
            'loop',
            'multiple',
            'muted',
            'nomodule',
            'novalidate',
            'open',
            'playsinline',
            'readonly',
            'required',
            'reversed',
            'selected',
        ], true);
    }

    private string $localName;
    private string $name;
    private ?string $namespaceURI;
    private ?string $prefix;
    private string $data;
    private ?ElementNode $owner;

    public function __construct(string $localName, ?string $ns = null, ?string $prefix = null)
    {
        $this->owner = null;
        $this->localName = $localName;
        $this->namespaceURI = $ns;
        $this->prefix = $prefix === '' ? null : $prefix;
        $this->name = $this->prefix === null ? $localName : $prefix . ':' . $localName;
        $this->data = '';
    }

    #region implements Attr properties

    public function localName(): string
    {
        return $this->localName;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function namespaceURI(): ?string
    {
        return $this->namespaceURI;
    }

    public function ownerElement(): ?ElementNode
    {
        return $this->owner;
    }

    public function prefix(): ?string
    {
        return $this->prefix;
    }

    public function value(): string
    {
        return $this->data;
    }

    public function valueSet(string $value): void
    {
        $old = $this->data;
        $this->data = $value;
        if ($this->owner && $old !== $value) {
            $this->owner->onAttrValueChanged($this);
        }
    }

    #endregion

    public function ownerElementSet(?ElementNode $owner): void
    {
        $oldOwner = $this->owner;
        $this->owner = $owner;
        if ($oldOwner === $owner) {
            return;
        }
        if ($oldOwner) {
            $oldOwner->onAttrRemoved($this);
        }
        if ($owner) {
            $owner->onAttrValueChanged($this);
        }
    }
}
