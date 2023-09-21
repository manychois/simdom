<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use BadMethodCallException;
use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\Dom\ElementNode;

/**
 * Represents a complex selector, i.e. a chain of selectors separated by combinators.
 */
class ComplexSelector extends AbstractSelector
{
    /**
     * The selectors in the chain.
     *
     * @var array<AbstractSelector>
     */
    public array $selectors = [];

    /**
     * The combinators in the chain.
     *
     * @var array<Combinator>
     */
    public array $combinators = [];

    /**
     * Creates a new ComplexSelector instance.
     *
     * @param AbstractSelector $first The leftmost selector in the chain.
     */
    public function __construct(AbstractSelector $first)
    {
        if ($first instanceof self) {
            throw new InvalidArgumentException('$first cannot be a ComplexSelector');
        }
        if ($first instanceof OrSelector) {
            throw new InvalidArgumentException('$first cannot be an OrSelector');
        }
        $this->selectors[] = $first;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $str = '';
        $selectorCount = count($this->selectors);
        $combinatorCount = count($this->combinators);
        for ($i = 0; $i < $selectorCount; ++$i) {
            $str .= $this->selectors[$i]->__toString();
            if ($i < $combinatorCount) {
                $str .= $this->combinators[$i]->value;
            }
        }

        return $str;
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        $len = count($this->selectors);
        if ($len === 1) {
            return $this->selectors[0]->matchWith($element);
        }

        $lastSelector = $this->selectors[$len - 1];
        if (!$lastSelector->matchWith($element)) {
            return false;
        }

        $reducedSelector = new self($this->selectors[0]);
        for ($i = 1; $i < $len - 1; ++$i) {
            $reducedSelector->selectors[] = $this->selectors[$i];
            $reducedSelector->combinators[] = $this->combinators[$i - 1];
        }

        $lastCombinator = $this->combinators[$len - 2];
        if ($lastCombinator === Combinator::Descendant) {
            foreach ($element->ancestors() as $ancestor) {
                if ($reducedSelector->matchWith($ancestor)) {
                    return true;
                }
            }
        } elseif ($lastCombinator === Combinator::Child) {
            $parent = $element->parentElement();
            if ($parent !== null && $reducedSelector->matchWith($parent)) {
                return true;
            }
        } elseif ($lastCombinator === Combinator::AdjacentSibling) {
            $prev = $element->prevElement();
            if ($prev !== null && $reducedSelector->matchWith($prev)) {
                return true;
            }
        } elseif ($lastCombinator === Combinator::GeneralSibling) {
            $parent = $element->parentElement();
            if ($parent === null) {
                return false;
            }
            $i = $element->index();
            while (--$i >= 0) {
                $node = $parent->childNodeAt($i);
                if ($node instanceof ElementNode && $reducedSelector->matchWith($node)) {
                    return true;
                }
            }
        } else {
            throw new BadMethodCallException(sprintf('Combinator "%s" is not supported', $lastCombinator->value));
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

        $copy = new self($this->selectors[0]->simplify());
        $count = count($this->selectors);
        for ($i = 1; $i < $count; ++$i) {
            $copy->selectors[] = $this->selectors[$i]->simplify();
            $copy->combinators[] = $this->combinators[$i - 1];
        }

        return $copy;
    }

    #endregion
}
