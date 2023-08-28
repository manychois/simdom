<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\DocumentTypeInterface;
use Manychois\Simdom\ElementInterface;
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
    public function setAttribute(string $name, ?string $value): void
    {
        $index = strtolower($name);
        $attr = $this->attrs[$index] ?? null;
        if ($attr === null) {
            $attr = new Attr($index, $value);
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

    #region extends AbstractParentNode

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
    protected function validatePreInsertion(array $nodes, ?AbstractNode $ref): void
    {
        parent::validatePreInsertion($nodes, $ref);
        foreach ($nodes as $node) {
            if ($node instanceof DocumentTypeInterface) {
                throw new InvalidArgumentException('DocumentType cannot be a child of an Element.');
            }
        }
    }

    /**
     * @inheritdoc
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
