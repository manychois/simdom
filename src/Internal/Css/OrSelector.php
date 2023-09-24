<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\StringStream;

/**
 * Represents a selector list that matches if any of the selectors match.
 */
class OrSelector extends AbstractSelector
{
    /**
     * Calculates the complexity of a selector.
     * It is used to sort selectors by complexity so that the simplest selector is tested first.
     *
     * @param AbstractSelector $selector The selector to get the complexity of.
     *
     * @return int The complexity of the selector.
     */
    public static function getComplexity(AbstractSelector $selector): int
    {
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
            if ($selector->type !== null) {
                $complexity += static::getComplexity($selector->type);
            }
            $complexity += intval(array_sum(array_map([__CLASS__, 'getComplexity'], $selector->selectors)));
        } elseif ($selector instanceof ComplexSelector) {
            $complexity += self::getComplexSelectorComplexity($selector);
        } elseif ($selector instanceof self) {
            $complexity += intval(array_sum(array_map([__CLASS__, 'getComplexity'], $selector->selectors)));
        }

        return $complexity;
    }

    /**
     * The list of selectors.
     *
     * @var array<AbstractSelector>
     */
    public array $selectors = [];

    /**
     * Parses an or selector.
     *
     * @param StringStream $str The string stream to parse.
     *
     * @return null|self The parsed or selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $orSelector = new self();
        SelectorParser::consumeWhitespace($str);
        $regex = '/\s*,?\s*/';
        while ($str->hasNext()) {
            $complexSelector = ComplexSelector::parse($str);
            if ($complexSelector === null) {
                throw new InvalidArgumentException(sprintf('Invalid character found: %s', $str->current()));
            }
            $orSelector->selectors[] = $complexSelector;
            $matchResult = $str->regexMatch($regex);
            assert($matchResult->success);
            if ($matchResult->value === '') {
                break;
            }
            $str->advance(strlen($matchResult->value));
        }

        if ($str->hasNext()) {
            throw new InvalidArgumentException(sprintf('Invalid character found: %s', $str->current()));
        }

        return $orSelector;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $str = '';
        foreach ($this->selectors as $selector) {
            $str .= $selector->__toString() . ',';
        }

        return substr($str, 0, -1);
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

        foreach ($this->selectors as $selector) {
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

        $copy = new self();
        foreach ($this->selectors as $selector) {
            $copy->selectors[] = $selector->simplify();
        }

        return $copy;
    }

    #endregion

        /**
     * Calculates the complexity of a complex selector.
     *
     * @param ComplexSelector $selector The complex selector to calculate the complexity of.
     *
     * @return int The complexity of the complex selector.
     */
    private static function getComplexSelectorComplexity(ComplexSelector $selector): int
    {
        $complexity = 0;
        $count = count($selector->selectors);
        for ($i = 0; $i < $count; ++$i) {
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

        return $complexity;
    }
}
