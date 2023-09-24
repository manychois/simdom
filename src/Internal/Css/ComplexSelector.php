<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use BadMethodCallException;
use Generator;
use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\StringStream;

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

    /**
     * Parses a complex selector.
     *
     * @param StringStream $str The string stream to parse.
     *
     * @return null|self The parsed complex selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $compound = CompoundSelector::parse($str);
        if ($compound === null) {
            return null;
        }

        $complex = new ComplexSelector($compound);

        while ($str->hasNext()) {
            $matchResult = $str->regexMatch('/\s*([>+~]|\\|\\|)?\s*/');
            if ($matchResult->value === '') {
                break;
            }

            $combinator = match ($matchResult->captures[0] ?? ' ') {
                ' ' => Combinator::Descendant,
                '>' => Combinator::Child,
                '+' => Combinator::AdjacentSibling,
                '~' => Combinator::GeneralSibling,
                '||' => throw new InvalidArgumentException('Column combinator is not supported'),
                default => throw new InvalidArgumentException('Invalid combinator found'),
            };
            $str->advance(strlen($matchResult->value));

            $compound = CompoundSelector::parse($str);
            if ($compound === null) {
                if ($combinator === Combinator::Descendant) {
                    // it is a whitespace not a combinator
                    break;
                }
                throw new InvalidArgumentException(
                    sprintf('Missing complex selector after combinator "%s"', $combinator->value),
                );
            }
            $complex->combinators[] = $combinator;
            $complex->selectors[] = $compound;
        }

        return $complex;
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

        $reduced = $this->discardRightmostSelector();

        $lastCombinator = $this->combinators[$len - 2];

        if ($lastCombinator === Combinator::Column) {
            throw new BadMethodCallException(sprintf('Combinator "%s" is not supported', $lastCombinator->value));
        }

        foreach (self::findCandidates($element, $lastCombinator) as $candidate) {
            if ($reduced->matchWith($candidate)) {
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

        $copy = new self($this->selectors[0]->simplify());
        $count = count($this->selectors);
        for ($i = 1; $i < $count; ++$i) {
            $copy->selectors[] = $this->selectors[$i]->simplify();
            $copy->combinators[] = $this->combinators[$i - 1];
        }

        return $copy;
    }

    #endregion

    /**
     * Returns a new ComplexSelector with the rightmost selector removed.
     *
     * @return ComplexSelector The new ComplexSelector with the rightmost selector removed.
     */
    private function discardRightmostSelector(): self
    {
        $reduced = new self($this->selectors[0]);
        $count = count($this->selectors) - 1;
        for ($i = 1; $i < $count; ++$i) {
            $reduced->selectors[] = $this->selectors[$i];
            $reduced->combinators[] = $this->combinators[$i - 1];
        }

        return $reduced;
    }

    /**
     * Returns possible candidate elements to match with the selector.
     *
     * @param ElementInterface $element    The element at the right of the combinator.
     * @param Combinator       $combinator The combinator.
     *
     * @return Generator<ElementInterface> The candidate elements.
     */
    private static function findCandidates(ElementInterface $element, Combinator $combinator): Generator
    {
        if ($combinator === Combinator::Descendant) {
            yield from $element->ancestors();
        } elseif ($combinator === Combinator::Child) {
            $parent = $element->parentElement();
            if ($parent !== null) {
                yield $parent;
            }
        } elseif ($combinator === Combinator::AdjacentSibling) {
            $prev = $element->prevElement();
            if ($prev !== null) {
                yield $prev;
            }
        } elseif ($combinator === Combinator::GeneralSibling) {
            yield from self::getGeneralSiblings($element);
        }
    }

    /**
     * Returns the general siblings of the element, from the nearest to the farthest.
     *
     * @param ElementInterface $element The element to get the general siblings of.
     *
     * @return Generator<ElementInterface> The general siblings, from the nearest to the farthest.
     */
    private static function getGeneralSiblings(ElementInterface $element): Generator
    {
        $parent = $element->parentElement();
        if ($parent !== null) {
            $prev = $element->prevElement();
            while ($prev !== null) {
                yield $prev;
                $prev = $prev->prevElement();
            }
        }
    }
}
