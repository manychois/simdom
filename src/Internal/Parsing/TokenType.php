<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

/**
 * Represents the type of a token.
 */
enum TokenType
{
    case Comment;
    case Doctype;
    case Eof;
    case EndTag;
    case StartTag;
    case Text;
}
