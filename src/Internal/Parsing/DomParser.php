<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\ElementNode;

/**
 * Represents a HTML DOM parser.
 */
class DomParser
{
    /**
     * Returns the current node, i.e. the top node of the open elements stack.
     *
     * @return ElementNode The current element node.
     */
    public function currentNode(): ElementNode
    {
        return new ElementNode('temp');
    }

    /**
     * Receives a token from the tokenizer.
     *
     * @param AbstractToken $token The token to receive.
     */
    public function receiveToken(AbstractToken $token): void
    {
    }
}
