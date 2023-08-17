<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\CommentInterface;
use Manychois\Simdom\NodeType;

/**
 * Internal implementation of CommentInterface.
 */
class CommentNode extends AbstractNode implements CommentInterface
{
    private string $sData;

    /**
     * Creates a new CommentNode with initial data.
     *
     * @param string $data The comment data.
     */
    public function __construct(string $data)
    {
        $this->sData = $data;
    }

    #region extends AbstractNode

    /**
     * @inheritDoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::Comment;
    }

    #endregion

    #region implements CommentInterface

    /**
     * @inheritDoc
     */
    public function data(): string
    {
        return $this->sData;
    }

    /**
     * @inheritDoc
     */
    public function setData(string $data): void
    {
        $this->sData = $data;
    }

    #endregion
}
