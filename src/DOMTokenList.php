<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use IteratorAggregate;
use Traversable;

interface DOMTokenList extends IteratorAggregate
{
    public function add(string ...$tokens): void;
    public function contains(string $token): bool;
    public function item(int $index): ?string;
    public function length(): int;
    public function remove(string ...$tokens): void;
    public function replace(string $old, string $new): bool;
    public function toggle(string $token, ?bool $force = null): bool;
    public function value(): string;

    /**
     * @return Traversable<string>
     */
    public function getIterator(): Traversable;
}
