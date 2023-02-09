<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParentNode;

/**
 * Represents the HTML document and serves as an entry point into the web page's content.
 */
interface Document extends ParentNode
{
    #region Document properties

    /**
     * Returns the `<body>` element of the document.
     */
    public function body(): ?Element;

    /**
     * Returns the Dcument Type Declaration (DTD) associated with the document.
     */
    public function doctype(): ?DocumentType;

    /**
     * Returns the root element of the document, usually the `<html>` element.
     */
    public function documentElement(): ?Element;

    /**
     * Returns the `<head>` element of the document.
     */
    public function head(): ?Element;

    #endregion
}
