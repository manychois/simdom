<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParentNode;

interface Document extends ParentNode
{
    #region Document properties

    public function body(): ?Element;
    public function doctype(): ?DocumentType;
    public function documentElement(): ?Element;
    public function head(): ?Element;

    #endregion
}
