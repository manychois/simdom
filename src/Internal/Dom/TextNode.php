<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\ElementInterface;
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

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        if ($this->pNode instanceof ElementInterface) {
            $name = $this->pNode->tagName();
            if (
                in_array($name, [
                    'IFRAME',
                    'NOEMBED',
                    'NOFRAMES',
                    'NOSCRIPT',
                    'SCRIPT',
                    'STYLE',
                    'TEMPLATE',
                    'XMP',
                ], true)
            ) {
                return $this->sData;
            }
        }

        return htmlspecialchars($this->sData, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
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
