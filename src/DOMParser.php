<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Provides the ability to parse a string of HTML into a DOM `Document`.
 */
interface DOMParser
{
    /**
     * Parses a string of HTML into a DOM `Document`.
     * @param string $source The string to be parsed.
     */
    public function parseFromString(string $source): Document;
}
