<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents a document type node in the DOM tree.
 */
interface DocumentTypeInterface extends NodeInterface
{
    /**
     * Returns the name of the document type.
     *
     * @return string The name of the document type.
     */
    public function name(): string;

    /**
     * Returns the public ID of the document type.
     *
     * @return string The public ID of the document type.
     */
    public function publicId(): string;

    /**
     * Returns the system ID of the document type.
     *
     * @return string The system ID of the document type.
     */
    public function systemId(): string;
}
