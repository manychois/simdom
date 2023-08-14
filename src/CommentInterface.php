<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a comment node in the DOM tree.
 */
interface CommentInterface extends NodeInterface
{
    /**
     * Returns the comment data.
     *
     * @return string The comment data.
     */
    public function data(): string;

    /**
     * Sets the comment data.
     *
     * @param string $data The comment data.
     *
     * @return void
     */
    public function setData(string $data): void;
}
