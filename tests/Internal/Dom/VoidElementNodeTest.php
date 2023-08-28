<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class VoidElementNodeTest extends TestCase
{
    public function testAppendExpectsException(): void
    {
        $br = Dom::createElement('br');
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('Element <br> cannot have child nodes.');
        $br->append(Dom::createComment());
    }
}
