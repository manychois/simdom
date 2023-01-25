<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

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
}
