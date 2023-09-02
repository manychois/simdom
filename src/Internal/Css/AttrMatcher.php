<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

/**
 * Represents an attribute matcher, i.e. how to compare the attribute value.
 */
enum AttrMatcher: string
{
    case Exists = '';
    case Equals = '=';
    case Includes = '~=';
    case DashMatch = '|=';
    case PrefixMatch = '^=';
    case SuffixMatch = '$=';
    case SubstringMatch = '*=';
}
