<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

/**
 * Represents a simplified version of attribute node.
 */
class Attr
{
    public string $index;
    public string $name;
    public ?string $value;
}
