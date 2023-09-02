<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;

/**
 * Represents a type selector.
 */
class TypeSelector extends AbstractSelector
{
    /**
     * The element tag name to match.
     *
     * @var string
     */
    public readonly string $type;

    /**
     * Creates a new TypeSelector instance.
     *
     * @param string $type The element tag name to match.
     */
    public function __construct(string $type)
    {
        $this->type = mb_strtolower($type, 'UTF-8');
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        return $this->type === '*' || $element->localName() === $this->type;
    }

    #endregion
}
