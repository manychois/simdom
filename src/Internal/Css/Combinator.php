<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

/**
 * Represents a combinator of two selectors.
 */
enum Combinator: string
{
    case Descendant = ' ';
    case Child = '>';
    case AdjacentSibling = '+';
    case GeneralSibling = '~';
    case Column = '||';
}
