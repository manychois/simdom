<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\NamespaceUri;
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
     * @param string $localName The local name of the element.
     */
    public function __construct(string $localName)
    {
        $this->name = strtolower($localName);
    }

    #region implements ElementInterface

    /**
     * @inheritdoc
     */
    public function hasAttribute(string $name): bool
    {
        $index = strtolower($name);

        return array_key_exists($index, $this->attrs);
    }

    /**
     * @inheritdoc
     */
    public function localName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function namespaceUri(): string
    {
        return NamespaceUri::Html->value;
    }

    /**
     * @inheritdoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::Element;
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $name, ?string $value): void
    {
        $index = strtolower($name);
        $attr = $this->attrs[$index] ?? null;
        if ($attr === null) {
            $attr = new Attr();
            $attr->index = $index;
            $attr->name = $name;
            $attr->value = $value;
            $this->attrs[$index] = $attr;
        } else {
            $attr->value = $value;
        }
    }

    #endregion
}
