<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

final class CommentToken implements Token
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
