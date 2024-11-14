<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a comment node in the DOM tree.
 */
class Comment extends AbstractNode
{
    public string $data;

    /**
     * Constructs a new instance of this class.
     *
     * @param string $data The data of the comment.
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
        return '';
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
        return NodeType::Comment;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $data = \str_replace('-->', '--&gt;', $this->data);

        return "<!--{$data}-->";
    }

    #endregion extends AbstractNode
}
