<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

/**
 * Represents a simplified version of attribute node.
 */
class Attr
{
    public readonly string $name;
    public ?string $value;

    /**
     * Creates an attribute node.
     *
     * @param string      $name  The name of the attribute.
     * @param null|string $value The value of the attribute.
     */
    public function __construct(string $name, ?string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}
