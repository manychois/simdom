<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Stringable;

/**
 * Base class for CSS selectors.
 */
abstract class AbstractSelector implements Stringable
{
    /**
     * Checks if the given element matches this selector.
     *
     * @param ElementInterface $element The element to check.
     *
     * @return bool True if the element matches this selector, false otherwise.
     */
    abstract public function matchWith(ElementInterface $element): bool;

    /**
     * Returns the string representation of this selector.
     *
     * @return string The string representation of this selector.
     */
    abstract public function __toString(): string;

    /**
     * Simplifies this selector's internal structure, if possible.
     *
     * @return AbstractSelector The simplified selector.
     */
    public function simplify(): self
    {
        return $this;
    }

    /**
     * Escapes the identifier for use in a CSS selector.
     *
     * @param string $ident The identifier to escape.
     *
     * @return string The escaped identifier.
     */
    protected function escIdent(string $ident): string
    {
        $s = preg_replace_callback('/[\\\\.#@\'">+~|]/', static fn ($matches) => match ($matches[0]) {
            '.' => '\\.',
            '#' => '\\#',
            '@' => '\\@',
            '\'' => '\\\'',
            '"' => '\\"',
            '>' => '\\>',
            '+' => '\\+',
            '~' => '\\~',
            '|' => '\\|',
            default => '\\\\',
        }, $ident);
        if ($s === null) {
            throw new InvalidArgumentException(preg_last_error_msg());
        }

        return $s;
    }
}
