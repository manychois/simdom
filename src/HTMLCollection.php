<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use IteratorAggregate;

interface HTMLCollection extends IteratorAggregate
{
    public function item(int $index): ?Element;
    public function length(): int;
}
