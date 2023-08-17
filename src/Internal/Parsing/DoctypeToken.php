<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\DoctypeNode;

/**
 * Represents a doctype token.
 */
class DoctypeToken extends AbstractToken
{
    public readonly DoctypeNode $node;

    /**
     * Creates a new instance of DoctypeNode.
     *
     * @param string $name     The name of the doctype.
     * @param string $publicId The public ID of the doctype.
     * @param string $systemId The system ID of the doctype.
     */
    public function __construct(string $name, string $publicId, string $systemId)
    {
        parent::__construct(TokenType::Doctype);
        $this->node = new DoctypeNode($name, $publicId, $systemId);
    }
}
