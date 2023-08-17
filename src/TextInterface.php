<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a text node in the DOM tree.
 */
interface TextInterface extends NodeInterface
{
    /**
     * Returns the text data.
     *
     * @return string The text data.
     */
    public function data(): string;

    /**
     * Sets the text data.
     *
     * @param string $data The text data.
     */
    public function setData(string $data): void;
}
