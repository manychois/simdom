<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Represents the insertion mode of the parser.
 */
enum InsertionMode
{
    case Initial;
    case BeforeHtml;
    case BeforeHead;
}
