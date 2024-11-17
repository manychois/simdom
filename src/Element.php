<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Css\MatchContext;
use Manychois\Simdom\Internal\AbstractParentNode;
use Manychois\Simdom\Internal\ElementKind;
use Manychois\Simdom\Parsing\DomParser;

/**
 * Represents an element node in the DOM tree.
 */
class Element extends AbstractParentNode
{
    public readonly string $tagName;
    private readonly ElementKind $kind;
    /**
     * @var array<string,string>
     */
    private array $attrMap = [];
    private ?DomTokenList $domTokenList = null;

    /**
     * Constructs a new instance of this class.
     *
     * @param string $tagName The tag name of the element.
     */
    public function __construct(string $tagName)
    {
        $this->tagName = \strtolower($tagName);
        $this->kind = ElementKind::identify($tagName);

        parent::__construct($this->kind === ElementKind::Void);
    }

    /**
     * Gets all attributes of this element.
     *
     * @return array<string,string> All attributes of this element.
     */
    public function attributes(): array
    {
        return $this->attrMap;
    }

    /**
     * Traverses the element and its parents (heading toward the document root) until it finds an element that matches
     * the specified CSS selector.
     *
     * @param string $selector The CSS selector string.
     *
     * @return self|null The first matching element, or null if no element matches the selector.
     */
    public function closest(string $selector): ?self
    {
        $selector = self::parseSelectorList($selector);
        $context = new MatchContext($this->root(), $this, []);
        foreach ($this->ancestors() as $node) {
            if ($node instanceof self && $selector->matches($context, $node)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Traverses the element and its parents (heading toward the document root) until it finds an element that matches
     * the predicate.
     *
     * @param callable $predicate The predicate to match.
     *
     * @return self|null The first matching element, or null if no element matches the predicate.
     *
     * @phpstan-param callable(self):bool $predicate
     */
    public function closestFn(callable $predicate): ?self
    {
        foreach ($this->ancestors() as $node) {
            if ($node instanceof self && $predicate($node)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the value of an attribute, or null if it does not exist.
     *
     * @param string $name The name of the attribute.
     *
     * @return string|null The value of the attribute, or null if it does not exist.
     */
    public function getAttr(string $name): ?string
    {
        $name = \strtolower($name);

        return $this->attrMap[$name] ?? null;
    }

    /**
     * Gets a live DomTokenListn of the class attributes of the element.
     *
     * @return DomTokenList The live DomTokenListn of the class attributes of the element.
     */
    public function classList(): DomTokenList
    {
        if ($this->domTokenList === null) {
            $this->domTokenList = new DomTokenList($this, 'class');
        }

        return $this->domTokenList;
    }

    /**
     * Checks if an attribute exists.
     *
     * @param string $name The name of the attribute.
     *
     * @return bool True if the attribute exists, false otherwise.
     */
    public function hasAttr(string $name): bool
    {
        $name = \strtolower($name);

        return \array_key_exists($name, $this->attrMap);
    }

    /**
     * Removes an attribute by name, if it exists.
     *
     * @param string $name The name of the attribute.
     */
    public function removeAttr(string $name): void
    {
        $name = \strtolower($name);
        unset($this->attrMap[$name]);
    }

    /**
     * Sets the value of an attribute.
     *
     * @param string $name  The name of the attribute.
     *
     * @param string $value The value of the attribute.
     */
    public function setAttr(string $name, string $value): void
    {
        if ($name === '') {
            throw new \InvalidArgumentException('The attribute name cannot be empty.');
        }

        $name = \strtolower($name);
        $this->attrMap[$name] = $value;

        if ($name !== 'class' || $this->domTokenList === null) {
            return;
        }

        $this->domTokenList->🚫markOutOfSync();
    }

    /**
     * Toggles the existence of an attribute.
     *
     * @param string    $name  The name of the attribute.
     * @param bool|null $force If true, adds the attribute; if false, removes the attribute; if null, toggles the
     *                         attribute.
     *
     * @return bool True if the attribute is present after the operation, false otherwise.
     */
    public function toggleAttr(string $name, ?bool $force = null): bool
    {
        $name = \strtolower($name);
        $current = $this->attrMap[$name] ?? null;
        if ($current === null) {
            if ($force === false) {
                return false;
            }

            $this->attrMap[$name] = '';

            return true;
        }

        if ($force === true) {
            return true;
        }

        unset($this->attrMap[$name]);

        return false;
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    #region Internal methods

    /**
     * Gets the kind of this element.
     *
     * @return ElementKind The kind of this element.
     *
     * @internal
     */
    public function 🚫getKind(): ElementKind
    {
        return $this->kind;
    }

    /**
     * Sets the value of an attribute, without normalizing and validating the name, and notifying the attribute change.
     *
     * @param string  $name  The lowercased name of the attribute.
     * @param ?string $value The value of the attribute. If null, the attribute is removed.
     */
    public function 🚫setAttr(string $name, ?string $value): void
    {
        if ($value === null) {
            unset($this->attrMap[$name]);
        } else {
            $this->attrMap[$name] = $value;
        }
    }

    #endregion Internal methods
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    #region extends AbstractParentNode

    /**
     * @inheritDoc
     */
    protected function validatePreInsertion(string|AbstractNode ...$futureChildren): void
    {
        if ($this->kind === ElementKind::Void && \count($futureChildren) > 0) {
            throw new \InvalidArgumentException(\sprintf('Cannot insert nodes to <%s>.', $this->tagName));
        }

        foreach ($futureChildren as $node) {
            if (\is_string($node)) {
                continue;
            }

            if ($node instanceof Document) {
                throw new \InvalidArgumentException('Document cannot be a child node.');
            }

            if ($this->kind === ElementKind::RawText || $this->kind === ElementKind::EscapableRawText) {
                if ($node instanceof self) {
                    throw new \InvalidArgumentException(\sprintf('Cannot insert an element to <%s>.', $this->tagName));
                }
            }

            if ($node->contains($this)) {
                throw new \InvalidArgumentException('Cannot insert the node itself or its ancestor.');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function clone(bool $deep = true): self
    {
        $clone = new self($this->tagName);
        $clone->attrMap = $this->attrMap;
        if ($deep) {
            $this->cloneChildNodeList($clone);
        }

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function equals(?AbstractNode $node): bool
    {
        if (!($node instanceof self) || $this->tagName !== $node->tagName) {
            return false;
        }

        if (\count($this->attrMap) !== \count($node->attrMap)) {
            return false;
        }
        foreach ($this->attrMap as $name => $value) {
            if (!\array_key_exists($name, $node->attrMap) || $node->attrMap[$name] !== $value) {
                return false;
            }
        }

        return $this->isEqualChildNodeList($node);
    }

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
    public function setInnerHtml(string $html): void
    {
        $domParser = new DomParser();
        $parsed = $domParser->parsePartial($html, $this->tagName);
        // apply the changes to a shallow clone first, to determine if the operation is valid
        $shallowClone = $this->clone(false);
        $shallowClone->append(...$parsed);
        // if the operation is valid, apply the changes to the original node
        $this->clear();
        $this->append(...$parsed);
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $html = '<' . $this->tagName;
        foreach ($this->attrMap as $name => $value) {
            $escName = \htmlspecialchars($name, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
            $escName = \str_replace(' ', '&#x20;', $escName);
            if ($value === '') {
                $html .= ' ' . $escName;
            } else {
                $escValue = \htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
                $html .= \sprintf(' %s="%s"', $escName, $escValue);
            }
        }
        $html .= '>';
        if ($this->kind !== ElementKind::Void) {
            foreach ($this->childNodeList as $node) {
                $html .= $node->toHtml();
            }
            $html .= '</' . $this->tagName . '>';
        }

        return $html;
    }

    #endregion extends AbstractParentNode
}
