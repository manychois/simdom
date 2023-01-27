<?php

declare(strict_types=1);

namespace Manychois\Simdom;

interface CharacterData extends Node
{
    #region CharacterData properties

    public function data(): string;
    public function dataSet(string $data): void;
    public function nextElementSibling(): ?Element;
    public function previousElementSibling(): ?Element;

    #endregion

    #region CharacterData methods

    public function after(Node|string ...$nodes): void;
    public function appendData(string $data): void;
    public function before(Node|string ...$nodes): void;
    public function remove(): void;
    public function replaceWith(Node|string ...$nodes): void;

    #endregion
}
