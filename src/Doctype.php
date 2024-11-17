<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents the document type.
 */
class Doctype
{
    public readonly string $name;
    public readonly string $publicId;
    public readonly string $systemId;

    /**
     * Constructs a new instance of this class.
     *
     * @param string $name     The name of the document type.
     * @param string $publicId The public identifier of the document type.
     * @param string $systemId The system identifier of the document type.
     */
    public function __construct(string $name, string $publicId = '', string $systemId = '')
    {
        $this->name = $name;
        $this->publicId = $publicId;
        $this->systemId = $systemId;
    }

    /**
     * Returns the HTML string of the document type.
     *
     * @return string The HTML string of the document type.
     */
    public function toHtml(): string
    {
        $format = '<!DOCTYPE %1$s>';
        if ($this->publicId === '') {
            if ($this->systemId !== '') {
                $format = '<!DOCTYPE %1$s SYSTEM "%3$s">';
            }
        } else {
            if ($this->systemId === '') {
                $format = '<!DOCTYPE %1$s PUBLIC "%2$s">';
            } else {
                $format = '<!DOCTYPE %1$s PUBLIC "%2$s" "%3$s">';
            }
        }

        return \sprintf($format, $this->name, $this->publicId, $this->systemId);
    }
}
