<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\StringStream;

/**
 * Represents a type selector.
 */
class TypeSelector extends AbstractSelector
{
    /**
     * The element tag name to match.
     *
     * @var string
     */
    public readonly string $type;

    /**
     * Creates a new TypeSelector instance.
     *
     * @param string $type The element tag name to match.
     */
    public function __construct(string $type)
    {
        $this->type = mb_strtolower($type, 'UTF-8');
    }

    /**
     * Parses a type selector.
     *
     * @param StringStream $str The string stream to parse.
     *
     * @return null|self The parsed type selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $chr = $str->current();
        if ($chr === '*') {
            $str->advance();

            return new TypeSelector('*');
        }

        $ident = SelectorParser::consumeIdentToken($str);
        if ($ident !== '') {
            return new TypeSelector($ident);
        }

        return null;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        return $this->type === '*' || $element->localName() === $this->type;
    }

    #endregion
}
