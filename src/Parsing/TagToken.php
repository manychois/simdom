<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

final class TagToken implements Token
{
    public string $name;
    public bool $isStartTag;
    public bool $isSelfClosing;
    /**
     * @var array<string, string>
     */
    public array $attributes;

    public function __construct(string $name, bool $isStartTag)
    {
        $this->name = $name;
        $this->isStartTag = $isStartTag;
        $this->isSelfClosing = false;
        $this->attributes = [];
    }

    public function oneOf(string ...$names): bool
    {
        return in_array($this->name, $names, true);
    }
}
