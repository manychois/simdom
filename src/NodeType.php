<?php

declare(strict_types=1);

namespace Manychois\Simdom;

enum NodeType : int
{
    case Element = 1;
    case Text = 3;
    case Comment = 8;
    case Document = 9;
    case DocumentType = 10;
    case DocumentFragment = 11;
}
