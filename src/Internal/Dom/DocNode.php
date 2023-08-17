<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\DocumentInterface;
use Manychois\Simdom\NodeType;

/**
 * Internal implementation of DocumentInterface
 */
class DocNode extends AbstractParentNode implements DocumentInterface
{
    #region extends AbstractParentNode

    /**
     * @inheritdoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::Document;
    }

    #endregion
}
