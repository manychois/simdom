<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Generator;
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
     * @param string $localName      The local name of the element.
     * @param bool   $forceLowercase Whether to force the local name to be lowercase.
     */
    public function __construct(string $localName, bool $forceLowercase = true)
    {
        $this->name = $forceLowercase ? strtolower($localName) : $localName;
    }

    #region implements ElementInterface

    /**
     * @inheritdoc
     */
    public function attributes(): Generator
    {
        foreach ($this->attrs as $attr) {
            yield $attr->name => $attr->value;
        }
    }

    /**
     * @inheritdoc
     */
    public function getAttribute(string $name): ?string
    {
        $index = strtolower($name);
        $attr = $this->attrs[$index] ?? null;

        return $attr === null ? null : $attr->value;
    }

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
    public function namespaceUri(): NamespaceUri
    {
        return NamespaceUri::Html;
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
            $attr = new Attr($name, $value);
            $this->attrs[$index] = $attr;
        } else {
            $attr->value = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function tagName(): string
    {
        return strtoupper($this->name);
    }

    #endregion
}
