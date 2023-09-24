<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\Internal\StringStream;

/**
 * Represents a subclass selector, i.e. a selector which is either an attribute selector, class selector or ID selector.
 */
abstract class AbstractSubclassSelector extends AbstractSelector
{
    /**
     * Parses a subclass selector.
     *
     * @param StringStream $str The string stream to parse.
     *
     * @return null|self The parsed subclass selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $chr = $str->current();

        return match ($chr) {
            '#' => IdSelector::parse($str),
            '.' => ClassSelector::parse($str),
            '[' => AttributeSelector::parse($str),
            default => null,
        };
    }
}
