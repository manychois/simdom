<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ElementKind;

/**
 * Represents a text node in the DOM tree.
 */
class Text extends AbstractNode
{
    public string $data;

    /**
     * Constructs a new instance of this class.
     *
     * @param string $data The data of the text.
     */
    public function __construct(string $data = '')
    {
        $this->data = $data;
    }

    #region extends AbstractNode

    /**
     * @inheritDoc
     */
    public function allTextData(array $excludes = ['head', 'script', 'style', 'template']): string
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function clone(bool $deep = true): self
    {
        return new self($this->data);
    }

    /**
     * @inheritDoc
     */
    public function equals(?AbstractNode $node): bool
    {
        return $node instanceof self && $this->data === $node->data;
    }

    /**
     * @inheritDoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::Text;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $onwer = $this->owner;
        if ($onwer === null) {
            $kind = ElementKind::Normal;
        } else {
            \assert($onwer instanceof Element);
            $kind = $onwer->🚫getKind();
        }

        return match ($kind) {
            ElementKind::RawText => $this->data,
            default => \htmlspecialchars($this->data, \ENT_NOQUOTES | \ENT_SUBSTITUTE, 'UTF-8'),
        };
    }

    #endregion extends AbstractNode
}
