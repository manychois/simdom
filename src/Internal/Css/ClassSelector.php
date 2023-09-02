<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;

/**
 * Represents a class selector.
 */
class ClassSelector extends AbstractSelector
{
    private readonly string $pattern;

    /**
     * The CSS class name to match.
     *
     * @var string
     */
    public readonly string $cssClass;

    /**
     * Creates a new ClassSelector instance.
     *
     * @param string $cssClass The CSS class name to match.
     */
    public function __construct(string $cssClass)
    {
        $this->cssClass = $cssClass;
        $this->pattern = '/(^|\s)' . preg_quote($cssClass, '/') . '(\s|$)/';
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '.' . static::escIdent($this->cssClass);
    }

        /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        $value = $element->getAttribute('class') ?? '';

        return preg_match($this->pattern, $value) === 1;
    }

    #endregion
}
