<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;

/**
 * Represents an ID selector.
 */
class IdSelector extends AbstractSelector
{
    /**
     * The ID to match.
     *
     * @var string
     */
    public readonly string $id;

    /**
     * Creates a new IdSelector instance.
     *
     * @param string $id The ID to match.
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '#' . static::escIdent($this->id);
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        return $element->id() === $this->id;
    }

    #endregion
}
