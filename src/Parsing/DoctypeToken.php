<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

final class DoctypeToken implements Token
{
    public ?string $name = null;
    public ?string $publicId = null;
    public ?string $systemId = null;
}
