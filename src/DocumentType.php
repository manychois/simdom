<?php

declare(strict_types=1);

namespace Manychois\Simdom;

interface DocumentType extends Node
{
    public function name(): string;
    public function publicId(): string;
    public function systemId(): string;

    #region DocumentType methods

    public function after(Node|string ...$nodes): void;
    public function before(Node|string ...$nodes): void;
    public function remove(): void;
    public function replaceWith(Node|string ...$nodes): void;

    #endregion
}
