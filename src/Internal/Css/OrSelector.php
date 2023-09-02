<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;

/**
 * Represents a selector list that matches if any of the selectors match.
 */
class OrSelector extends AbstractSelector
{
    /**
     * Returns the complexity of a selector.
     * It is used to sort selectors by complexity so that the simplest selector is tested first.
     *
     * @param null|AbstractSelector $selector The selector to get the complexity of.
     *
     * @return int The complexity of the selector.
     */
    public static function getComplexity(?AbstractSelector $selector): int
    {
        if ($selector === null) {
            return 0;
        }
        if ($selector instanceof TypeSelector) {
            return 1;
        }
        if ($selector instanceof IdSelector) {
            return 2;
        }
        if ($selector instanceof ClassSelector) {
            return 3;
        }
        if ($selector instanceof AttributeSelector) {
            return 4;
        }

        $complexity = 0;
        if ($selector instanceof CompoundSelector) {
            $complexity += static::getComplexity($selector->type);
            foreach ($selector->selectors as $s) {
                $complexity += static::getComplexity($s) + 4;
            }
        } elseif ($selector instanceof ComplexSelector) {
            for ($i = 0; $i < count($selector->selectors); ++$i) {
                $complexity += static::getComplexity($selector->selectors[$i]);
                if ($i > 0) {
                    $complexity += match ($selector->combinators[$i - 1]) {
                        Combinator::Descendant => 100,
                        Combinator::Child => 5,
                        Combinator::AdjacentSibling => 5,
                        Combinator::GeneralSibling => 10,
                        Combinator::Column => 10,
                    };
                }
            }
        } elseif ($selector instanceof self) {
            foreach ($selector->selectors as $s) {
                $complexity += static::getComplexity($s);
            }
        }

        return $complexity;
    }

    /**
     * The list of selectors.
     *
     * @var array<AbstractSelector>
     */
    public array $selectors = [];

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $s = '';
        foreach ($this->selectors as $selector) {
            $s .= $selector->__toString() . ',';
        }

        return substr($s, 0, -1);
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        if (count($this->selectors) === 0) {
            return false;
        }
        if (count($this->selectors) === 1) {
            return $this->selectors[0]->matchWith($element);
        }

        $sorted = $this->selectors;
        usort($sorted, static fn ($a, $b) => static::getComplexity($a) <=> static::getComplexity($b));
        foreach ($sorted as $selector) {
            if ($selector->matchWith($element)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function simplify(): AbstractSelector
    {
        if (count($this->selectors) === 1) {
            return $this->selectors[0]->simplify();
        }

        $or = new self();
        foreach ($this->selectors as $selector) {
            $or->selectors[] = $selector->simplify();
        }

        return $or;
    }

    #endregion
}
