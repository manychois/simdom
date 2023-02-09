<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents options for pretty printing a node.
 */
class PrettyPrintOption
{
    /**
     * Whether to print a self-closing slash for void elements. Default `false`.
     */
    public bool $selfClosingSlash = false;

    /**
     * Whether to escape attribute values. Default `true`.
     */
    public bool $escAttrValue = true;

    /**
     * The indentation string. Default ` `.
     */
    public string $indent = '  ';
}
