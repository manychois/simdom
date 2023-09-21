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

    /**
     * Returns the HTML representation of the attribute.
     *
     * @return string The HTML representation of the attribute.
     */
    public function toHtml(): string
    {
        if ($this->value === null) {
            return $this->name;
        }

        $escaped = htmlspecialchars($this->value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        return sprintf('%s="%s"', $this->name, $escaped);
    }
}
