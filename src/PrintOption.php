<?php

declare(strict_types=1);

namespace Manychois\Simdom;

class PrintOption
{
    public bool $selfClosingSlash = false;
    public bool $escAttrValue = true;
    public string $indent = '  ';
    public bool $prettyPrint = false;
}
