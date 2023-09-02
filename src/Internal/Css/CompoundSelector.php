<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;

/**
 * Represents a compound selector.
 */
class CompoundSelector extends AbstractSelector
{
    /**
     * The type selector.
     */
    public ?TypeSelector $type = null;

    /**
     * The list of ID, class, or attribute selectors in the compound selector.
     *
     * @var array<IdSelector|ClassSelector|AttributeSelector>
     */
    public array $selectors = [];

    #region extends AbstractSelector

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        $s = '';
        if ($this->type !== null) {
            $s .= $this->type->__toString();
        }

        foreach ($this->selectors as $selector) {
            $s .= $selector->__toString();
        }

        return $s;
    }

    /**
     * @inheritdoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        if ($this->type !== null && !$this->type->matchWith($element)) {
            return false;
        }

        foreach ($this->selectors as $selector) {
            if (!$selector->matchWith($element)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function simplify(): AbstractSelector
    {
        if ($this->type !== null && count($this->selectors) === 0) {
            return $this->type->simplify();
        }

        if ($this->type === null && count($this->selectors) === 1) {
            return $this->selectors[0]->simplify();
        }

        return $this;
    }

    #endregion
}
