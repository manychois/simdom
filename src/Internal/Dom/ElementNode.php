<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\DocumentTypeInterface;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\Parsing\DomParser;
use Manychois\Simdom\NamespaceUri;
use Manychois\Simdom\NodeType;

/**
 * Internal implementation of ElementInterface
 */
class ElementNode extends AbstractParentNode implements ElementInterface
{
    protected readonly string $name;
    /**
     * @var array<string, Attr>
     */
    protected array $attrs = [];

    /**
     * Creates an element node.
     *
     * @param string $localName      The local name of the element.
     * @param bool   $forceLowercase Whether to force the local name to be lowercase.
     */
    public function __construct(string $localName, bool $forceLowercase = true)
    {
        parent::__construct();
        $this->name = $forceLowercase ? strtolower($localName) : $localName;
    }

    #region implements ElementInterface

    /**
     * @inheritDoc
     */
    public function ancestors(): Generator
    {
        $parent = $this->pNode;
        while ($parent !== null && $parent instanceof ElementInterface) {
            yield $parent;
            $parent = $parent->pNode;
        }
    }

    /**
     * @inheritDoc
     */
    public function attributes(): Generator
    {
        foreach ($this->attrs as $attr) {
            yield $attr->name => $attr->value;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAttribute(string $name): ?string
    {
        $index = strtolower($name);
        $attr = $this->attrs[$index] ?? null;

        return $attr === null ? null : $attr->value;
    }

    /**
     * @inheritDoc
     */
    public function hasAttribute(string $name): bool
    {
        $index = strtolower($name);

        return array_key_exists($index, $this->attrs);
    }

    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return $this->getAttribute('id') ?? '';
    }

    /**
     * @inheritDoc
     */
    public function innerHtml(): string
    {
        return $this->cNodes->toHtml();
    }

    /**
     * @inheritDoc
     */
    public function localName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function namespaceUri(): NamespaceUri
    {
        return NamespaceUri::Html;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $name, ?string $value): ElementInterface
    {
        $index = strtolower($name);
        $attr = $this->attrs[$index] ?? null;
        if ($attr === null) {
            $attr = new Attr($index, $value);
            $this->attrs[$index] = $attr;
        } else {
            $attr->value = $value;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setInnerHtml(string $html): ElementInterface
    {
        $parser = new DomParser();
        $newChildren = $parser->parsePartial($html, $this);
        $this->clear();
        foreach ($newChildren as $newChild) {
            $this->fastAppend($newChild);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tagName(): string
    {
        return strtoupper($this->name);
    }

    #endregion

    #region extends AbstractParentNode

    /**
     * @inheritDoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::Element;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $html = '<' . $this->name;
        foreach ($this->attrs as $attr) {
            $html .= ' ' . $attr->toHtml();
        }
        $html .= '>';
        $html .= $this->cNodes->toHtml();
        $html .= sprintf('</%s>', $this->name);

        return $html;
    }

    /**
     * @inheritDoc
     */
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): int
    {
        $index = parent::validatePreInsertion($nodes, $ref);
        foreach ($nodes as $node) {
            if ($node instanceof DocumentTypeInterface) {
                throw new InvalidArgumentException('DocumentType cannot be a child of an Element.');
            }
        }

        return $index;
    }

    /**
     * @inheritDoc
     */
    protected function validatePreReplace(AbstractNode $old, array $newNodes): int
    {
        $index = parent::validatePreReplace($old, $newNodes);
        foreach ($newNodes as $new) {
            if ($new instanceof DocumentTypeInterface) {
                throw new InvalidArgumentException('DocumentType cannot be a child of an Element.');
            }
        }

        return $index;
    }

    #endregion
}
