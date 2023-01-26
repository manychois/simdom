<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\Comment;
use Manychois\Simdom\NodeType;

class CommentNode extends CharNode implements Comment
{
    #region overrides BaseNode

    public function nodeType(): NodeType
    {
        return NodeType::Comment;
    }

    public function serialize(): string
    {
        return '<!--' . $this->data . '-->';
    }

    #endregion
}
