<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Simdom\ChildNodeList;

/**
 * Represents an empty child node list which cannot be modified.
 * All void elements share the same instance of this class.
 */
class EmptyChildNodeList extends ChildNodeList
{
    private static ?self $instance = null;

    /**
     * Gets the singleton instance of this class.
     *
     * @return self The singleton instance of this class.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructs a new instance of this class.
     */
    private function __construct()
    {
        // Do nothing.
    }
}
