<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\StringStream;

/**
 * Represents a class selector.
 */
class ClassSelector extends AbstractSubclassSelector
{
    /**
     * The regular expression pattern to match.
     *
     * @var non-empty-string
     */
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

    /**
     * Parses a class selector.
     *
     * @param StringStream $str The string stream to parse.
     *
     * @return null|self The parsed class selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $chr = $str->current();
        assert($chr === '.');
        $str->advance();
        $ident = SelectorParser::consumeIdentToken($str);
        if ($ident === '') {
            throw new InvalidArgumentException('Invalid class selector found');
        }

        return new ClassSelector($ident);
    }

    #region extends AbstractSubclassSelector

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
