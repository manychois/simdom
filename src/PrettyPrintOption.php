<?php

declare(strict_types=1);

namespace Manychois\Simdom;

class PrettyPrintOption
{
    public bool $selfClosingSlash = false;
    public bool $escAttrValue = true;
    public string $indent = '  ';
}
