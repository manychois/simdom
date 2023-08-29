<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\DocumentTypeInterface;
use Manychois\Simdom\NodeType;

/**
 * Internal implementation of DocumentTypeInterface
 */
class DoctypeNode extends AbstractNode implements DocumentTypeInterface
{
    private readonly string $name;
    private readonly string $publicId;
    private readonly string $systemId;

    /**
     * Creates a new instance of DoctypeNode
     *
     * @param string $name     The name of the document type
     * @param string $publicId The public identifier of the document type
     * @param string $systemId The system identifier of the document type
     */
    public function __construct(string $name, string $publicId, string $systemId)
    {
        if (strpos($name, '>') !== false) {
            throw new InvalidArgumentException('Invalid character ">" in document type name.');
        }
        $this->name = $name;

        $pbrk = strpbrk($publicId, '\'">');
        if ($pbrk !== false) {
            throw new InvalidArgumentException(
                sprintf('Invalid character "%s" in document type public identifier.', $pbrk[0])
            );
        }
        $this->publicId = $publicId;

        $pbrk = strpbrk($systemId, '\'">');
        if ($pbrk !== false) {
            throw new InvalidArgumentException(
                sprintf('Invalid character "%s" in document type system identifier.', $pbrk[0])
            );
        }
        $this->systemId = $systemId;
    }

    #region extends AbstractNode

    /**
     * @inheritDoc
     */
    public function nodeType(): NodeType
    {
        return NodeType::DocumentType;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $html = "<!DOCTYPE {$this->name}";
        if ($this->publicId !== '') {
            $html .= " PUBLIC \"{$this->publicId}\"";
        } elseif ($this->systemId !== '') {
            $html .= " SYSTEM";
        }
        if ($this->systemId !== '') {
            $html .= " \"{$this->systemId}\"";
        }
        $html .= '>';

        return $html;
    }

    #endregion

    #region implements DocumentTypeInterface

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function publicId(): string
    {
        return $this->publicId;
    }

    /**
     * @inheritDoc
     */
    public function systemId(): string
    {
        return $this->systemId;
    }

    #endregion
}
