<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\NodeType;
use Manychois\Simdom\TextInterface;

/**
 * Internal implementation of TextInterface.
 */
class TextNode extends AbstractNode implements TextInterface
{
    private string $sData;

    /**
     * Creates a new TextNode with initial data.
     *
     * @param string $data The text data.
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
        return NodeType::Text;
    }

    #endregion

    #region implements TextInterface

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
