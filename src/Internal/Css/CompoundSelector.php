<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\StringStream;

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
     * The list of subclass selectors in the compound selector.
     *
     * @var array<AbstractSubclassSelector>
     */
    public array $selectors = [];

    /**
     * Parses a compound selector.
     *
     * @param StringStream $str The string stream to parse.
     *
     * @return null|self The parsed compound selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $compound = new CompoundSelector();
        $compound->type = TypeSelector::parse($str);

        while ($str->hasNext()) {
            $whitespace = SelectorParser::consumeWhitespace($str);
            $subclass = AbstractSubclassSelector::parse($str);
            if ($subclass === null) {
                // undo the whitespace consumption, as this could be descendant combinator ( ).
                $str->prepend($whitespace);
                break;
            }
            $compound->selectors[] = $subclass;
        }

        if ($compound->type === null && count($compound->selectors) === 0) {
            return null;
        }

        return $compound;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $str = '';
        if ($this->type !== null) {
            $str .= $this->type->__toString();
        }

        foreach ($this->selectors as $selector) {
            $str .= $selector->__toString();
        }

        return $str;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
