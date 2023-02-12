<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Comment;
use Manychois\Simdom\Node;

class CommentNode extends CharNode implements Comment
{
    #region overrides BaseNode

    public function nodeType(): int
    {
        return Node::COMMENT_NODE;
    }

    public function serialize(): string
    {
        return '<!--' . $this->data . '-->';
    }

    #endregion
}
