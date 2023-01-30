<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Attr;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\NamedNodeMap;
use Traversable;

class AttrList implements NamedNodeMap
{
    public readonly ElementNode $owner;
    /**
     * @var array<string, AttrNode>
     */
    private array $attrs;

    public function __construct(ElementNode $owner)
    {
        $this->attrs = [];
        $this->owner = $owner;
    }

    #region implement NamedNodeMap properties

    public function length(): int
    {
        return count($this->attrs);
    }

    #endregion

    #region implement NamedNodeMap methods

    /**
     * @return Traversable<Attr>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->attrs as $attr) {
            yield $attr;
        }
    }

    public function getNamedItem(string $name): ?Attr
    {
        return $this->attrs[$name] ?? null;
    }

    public function getNamedItemNS(?DomNs $ns, string $localName): ?Attr
    {
        foreach ($this->attrs as $attr) {
            if ($attr->localName() === $localName && $attr->namespaceURI() === $ns) {
                return $attr;
            }
        }
        return null;
    }

    public function item(int $index): ?Attr
    {
        if ($index < -1 || $index >= count($this->attrs)) {
            return null;
        }
        $i = 0;
        foreach ($this->attrs as $attr) {
            if ($i++ === $index) {
                return $attr;
            }
        }
    }

    public function removeNamedItem(string $name): Attr
    {
        if (array_key_exists($name, $this->attrs)) {
            $attr = $this->attrs[$name];
            unset($this->attrs[$name]);
            $attr->ownerElementSet(null);
            return $attr;
        }
        throw new InvalidArgumentException("Attr {$name} not found.");
    }

    public function removeNamedItemNS(?DomNs $ns, string $localName): Attr
    {
        foreach ($this->attrs as $name => $attr) {
            if ($attr->localName() === $localName && $attr->namespaceURI() === $ns) {
                unset($this->attrs[$name]);
                $attr->ownerElementSet(null);
                return $attr;
            }
        }
        throw new InvalidArgumentException("Attr $localName not found.");
    }

    public function setNamedItem(Attr $attr): ?Attr
    {
        /** @var AttrNode $attr */
        $aOwner = $attr->ownerElement();
        $qualifiedName = $attr->name();
        if ($aOwner && $aOwner !== $this->owner) {
            throw new InvalidArgumentException("Attr $qualifiedName is already in use.");
        }
        $existing = $this->attrs[$qualifiedName] ?? null;
        if ($existing === $attr) {
            return $attr;
        }
        $existing?->ownerElement(null);
        $this->attrs[$qualifiedName] = $attr;
        $attr->ownerElementSet($this->owner);
        return $existing;
    }

    #endregion

    public function set(string $name, string $value): AttrNode
    {
        $existing = $this->attrs[$name] ?? null;
        if ($existing) {
            $existing->valueSet($value);
            return $existing;
        }
        $newAttr = new AttrNode($name);
        $newAttr->valueSet($value);
        $this->attrs[$name] = $newAttr;
        $newAttr->ownerElementSet($this->owner);
        return $newAttr;
    }

    public function setNS(?DomNs $ns, ?string $prefix, string $localName, string $value): AttrNode
    {
        foreach ($this->attrs as $attr) {
            if ($attr->localName() === $localName && $attr->namespaceURI() === $ns) {
                $attr->valueSet($value);
                return $attr;
            }
        }
        $newAttr = new AttrNode($localName, $ns, $prefix);
        $newAttr->valueSet($value);
        $this->attrs[$newAttr->name()] = $newAttr;
        $newAttr->ownerElementSet($this->owner);
        return $newAttr;
    }
}
