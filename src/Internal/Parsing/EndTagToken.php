<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Represents a end tag token.
 */
class EndTagToken extends AbstractToken
{
    public readonly string $tagName;

    /**
     * Creates a new end tag token.
     *
     * @param string $tagName The tag name.
     */
    public function __construct(string $tagName)
    {
        parent::__construct(TokenType::EndTag);
        $this->tagName = $tagName;
    }

    /**
     * Returns whether the tag name is one of the given tag names.
     *
     * @param string ...$tagNames The tag names to check.
     *
     * @return bool Whether the tag name is one of the given tag names.
     */
    public function isOneOf(string ...$tagNames): bool
    {
        return in_array($this->tagName, $tagNames, true);
    }
}
