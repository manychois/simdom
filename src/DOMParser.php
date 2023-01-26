<?php

declare(strict_types=1);

namespace Manychois\Simdom;

interface DOMParser
{
    public function parseFromString(string $source): Document;
}
