<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParentNode;

interface Node
{
    #region Node properties

    public function nextSibling(): ?Node;
    public function nodeType(): NodeType;
    public function ownerDocument(): ?Document;
    public function parentElement(): ?Element;
    public function parentNode(): ?ParentNode;
    public function previousSibling(): ?Node;
    public function textContent(): ?string;
    public function textContentSet(string $data): void;

    #endregion

    #region Node methods

    public function cloneNode(bool $deep = false): static;
    public function isEqualNode(Node $node): bool;
    public function getRootNode(): Node;

    #endregion
}
