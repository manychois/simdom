<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Attr;
use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeType;
use Manychois\Simdom\Parsing\Parser;

class ElementNode extends BaseParentNode implements Element
{
    use ChildNodeMixin;
    use NonDocumentTypeChildNodeMixin;

    public static function isVoid(string $localName): bool
    {
        return in_array(
            $localName,
            [
                'area',
                'base',
                'basefont',
                'bgsound',
                'br',
                'col',
                'command',
                'embed',
                'frame',
                'hr',
                'image',
                'img',
                'input',
                'isindex',
                'keygen',
                'link',
                'menuitem',
                'meta',
                'nextid',
                'param',
                'source',
                'track',
                'wbr',
            ],
            true
        );
    }

    public bool $isInternalAttrChange = false;
    private readonly DomNs $namespaceURI;
    private readonly string $localName;
    /**
     * It is not initialized until `attributes()` is called.
     */
    private ?AttrList $attrList;
    /**
     * It is not initialized until `classList()` is called.
     */
    private ?ClassList $clsList;

    public function __construct(string $localName, DomNs $ns = DomNs::Html)
    {
        parent::__construct();
        $this->localName = $localName;
        $this->namespaceURI = $ns;
        $this->attrList = null;
        $this->clsList = null;
    }

    #region implement Element properties

    public function attributes(): AttrList
    {
        if ($this->attrList === null) {
            $this->attrList = new AttrList($this);
        }
        return $this->attrList;
    }

    public function classList(): ClassList
    {
        if ($this->clsList === null) {
            $this->clsList = new ClassList($this);
        }
        return $this->clsList;
    }

    public function className(): string
    {
        return $this->getAttribute('class') ?? '';
    }

    public function classNameSet(string $value): void
    {
        $this->setAttribute('class', $value);
    }

    public function id(): string
    {
        return $this->getAttribute('id') ?? '';
    }

    public function idSet(string $value): void
    {
        $this->setAttribute('id', $value);
    }

    public function innerHTML(): string
    {
        if ($this->namespaceURI === DomNs::Html && $this->isVoid($this->localName)) {
            return '';
        }
        $s = '';
        if ($this->nodeList) {
            foreach ($this->nodeList as $child) {
                $s .= $child->serialize();
            }
        }
        return $s;
    }

    public function innerHTMLSet(string $value): void
    {
        $parser = new Parser();
        $nodeList = $this->nodeList;
        $nodeList->clear();
        $newChildren = $parser->parsePartial($this, $value);
        $nodeList->simAppend(...$newChildren);
    }

    public function localName(): string
    {
        return $this->localName;
    }

    public function namespaceURI(): DomNs
    {
        return $this->namespaceURI;
    }

    public function outerHTML(): string
    {
        $s = '<' . $this->localName;
        if ($this->attrList?->length()) {
            $sa = [];
            foreach ($this->attrList as $attr) {
                $name = $attr->name();
                $value = $attr->value();
                if ($value === '' && AttrNode::isBoolean($name)) {
                    $sa[] = $name;
                } else {
                    $sa[] = $name . '="' . BaseNode::escapeString($value, true) . '"';
                }
            }
            $s .= ' ' . implode(' ', $sa);
        }
        $s .= '>';
        if ($this->namespaceURI === DomNs::Html && static::isVoid($this->localName)) {
            return $s;
        }
        $s .= $this->innerHTML();
        $s .= '</' . $this->localName . '>';
        return $s;
    }

    public function outerHTMLSet(string $value): void
    {
        $parent = $this->parent;
        if ($parent instanceof Document) {
            throw new InvalidArgumentException('Root element cannot be modified via outerHTMLSet().');
        }
        if ($parent === null) {
            return;
        }
        if ($parent instanceof DocFragNode) {
            $temp = new ElementNode('body');
            $temp->innerHTMLSet($value);
            $new = new DocFragNode();
            foreach ($temp->childNodes() as $child) {
                $new->appendChild($child);
            }
            $parent->replaceChild($new, $this);
        } else {
            assert($parent instanceof ElementNode);
            $new = new ElementNode($parent->localName);
            $new->innerHTMLSet($value);
            $this->replaceWith(...$new->childNodes());
        }
    }

    public function tagName(): string
    {
        return $this->namespaceURI === DomNs::Html ? strtoupper($this->localName) : $this->localName;
    }

    #endregion

    #region implement Element methods (attributes related)

    public function getAttribute(string $name): ?string
    {
        $attr = $this->attributes()->getNamedItem($name);
        return $attr?->value();
    }

    /**
     * @return array<string>
     */
    public function getAttributeNames(): array
    {
        $names = [];
        foreach ($this->attributes() as $attr) {
            $names[] = $attr->name();
        }
        return $names;
    }

    public function getAttributeNode(string $name): ?Attr
    {
        return $this->attributes()->getNamedItem($name);
    }

    public function getAttributeNodeNS(?DomNs $ns, string $localName): ?Attr
    {
        return $this->attributes()->getNamedItemNS($ns, $localName);
    }

    public function getAttributeNS(?DomNs $ns, string $localName): ?string
    {
        $attr = $this->attributes()->getNamedItemNS($ns, $localName);
        return $attr?->value();
    }

    public function hasAttribute(string $name): bool
    {
        return $this->attributes()->getNamedItem($name) !== null;
    }

    public function hasAttributeNS(?DomNs $ns, string $localName): bool
    {
        return $this->attributes()->getNamedItemNS($ns, $localName) !== null;
    }

    public function hasAttributes(): bool
    {
        return $this->attributes()->length() > 0;
    }

    public function removeAttribute(string $name): void
    {
        $attrs = $this->attributes();
        if ($attrs->getNamedItem($name) !== null) {
            $attrs->removeNamedItem($name);
        }
    }

    public function removeAttributeNode(Attr $attr): Attr
    {
        if ($attr->ownerElement() !== $this) {
            throw new InvalidArgumentException('The attribute is not owned by this element.');
        }
        $this->attributes()->removeNamedItem($attr->name());
        return $attr;
    }

    public function removeAttributeNS(?DomNs $ns, string $localName): void
    {
        $this->attributes()->removeNamedItemNS($ns, $localName);
    }

    public function setAttribute(string $name, string $value): void
    {
        $this->attributes()->set($name, $value);
    }

    public function setAttributeNode(Attr $attr): ?Attr
    {
        return $this->attributes()->setNamedItem($attr);
    }

    public function setAttributeNS(?DomNs $ns, string $name, string $value): void
    {
        list($prefix, $localName) = $this->extractPrefixLocalName($ns, $name);
        $this->attributes()->setNS($ns, $prefix, $localName, $value);
    }

    public function toggleAttribute(string $name, ?bool $force = null): bool
    {
        if ($this->namespaceURI === DomNs::Html) {
            $name = strtolower($name);
        }
        $attrs = $this->attributes();
        $attr = $attrs->getNamedItem($name);
        if ($attr === null) {
            if ($force !== false) {
                $attrs->set($name, '');
                return true;
            }
            return false;
        }
        if ($force !== true) {
            $attrs->removeNamedItem($name);
            return false;
        }
        return true;
    }

    #endregion

    #region overrides BaseParentNode properties

    public function nodeType(): NodeType
    {
        return NodeType::Element;
    }

    #endregion

    #region overrides BaseParentNode methods

    public function cloneNode(bool $deep = false): static
    {
        $clone = new static($this->localName, $this->namespaceURI);
        $cloneAttrs = $clone->attributes();
        foreach ($this->attributes() as $attr) {
            $cloneAttrs->setNs($attr->namespaceURI(), $attr->prefix(), $attr->localName(), $attr->value());
        }
        if ($deep) {
            foreach ($this->nodeList as $child) {
                $clone->nodeList->simAppend($child->cloneNode(true));
            }
        }
        return $clone;
    }

    public function isEqualNode(Node $node): bool
    {
        if (!parent::isEqualNode($node)) {
            return false;
        }
        if (
            !$node instanceof Element
            || $node->localName() !== $this->localName
            || $node->namespaceURI() !== $this->namespaceURI
        ) {
            return false;
        }
        if ($node->attributes()->length() !== $this->attributes()->length()) {
            return false;
        }
        foreach ($this->attributes() as $attr) {
            if ($node->attributes()->getNamedItem($attr->name())?->value() !== $attr->value()) {
                return false;
            }
        }
        return parent::isEqualNode($node);
    }

    public function serialize(): string
    {
        return $this->outerHTML();
    }

    /**
     * @param array<Node> $nodes
     */
    public function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        parent::validatePreInsertion($child, $nodes);
        $getEx = fn (Node $node, string $msg) => new PreInsertionException($this, $node, $child, $msg);
        foreach ($nodes as $node) {
            if ($node instanceof DocumentType) {
                throw $getEx($node, 'DocumentType cannot be a child of an Element.');
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        parent::validatePreReplace($old, $newNodes);
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, $old, $msg);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentType) {
                throw $getEx($new, 'DocumentType cannot be a child of an Element.');
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    public function validatePreReplaceAll(array $newNodes): void
    {
        parent::validatePreReplaceAll($newNodes);
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, null, $msg);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentType) {
                throw $getEx($new, 'DocumentType cannot be a child of an Element.');
            }
        }
    }

    #endregion

    public function onAttrValueChanged(AttrNode $attr): void
    {
        if ($this->isInternalAttrChange) {
            return;
        }
        if ($attr->name() === 'class' && $this->clsList) {
            $this->clsList->reset($attr->value());
        }
    }

    public function onAttrRemoved(AttrNode $attr): void
    {
        // No internal change of AttrNode removal.
        // if ($this->isInternalAttrChange) {
        //     return;
        // }
        if ($attr->name() === 'class' && $this->clsList) {
            $this->clsList->reset(null);
        }
    }

    /**
     * @return array<string>
     */
    private function extractPrefixLocalName(?DomNs $ns, string $qualifiedName): array
    {
        // TODO: validate qualifiedName
        $parts = explode(':', $qualifiedName, 2);
        if (count($parts) === 1) {
            $prefix = null;
            $localName = $parts[0];
        } else {
            list($prefix, $localName) = $parts;
        }
        if ($prefix !== null && $ns === null) {
            throw new InvalidArgumentException('Namespace must be specified when prefix is specified.');
        }
        if ($prefix === 'xml' && $ns !== DomNs::Xml) {
            throw new InvalidArgumentException("Expects XML namespace for prefix xml.");
        }
        if (($qualifiedName === 'xmlns' || $prefix === 'xmlns') && $ns !== DomNs::XmlNs) {
            throw new InvalidArgumentException("Invalid namespace for XMLNS.");
        }
        if ($ns === DomNs::XmlNs && ($qualifiedName !== 'xmlns' && $prefix !== 'xmlns')) {
            throw new InvalidArgumentException("Expects prefix xmlns for XMLNS namespace.");
        }
        return [$prefix, $localName];
    }
}
