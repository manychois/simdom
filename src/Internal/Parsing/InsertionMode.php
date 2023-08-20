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
    case InHead;
    case AfterHead;
    case InBody;
    case AfterBody;
    case AfterAfterBody;
    case ForeignContent;
}
