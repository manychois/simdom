<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Css;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\Css\IdSelector;
use PHPUnit\Framework\TestCase;

class IdSelectorTest extends TestCase
{
    public function testMatchWith(): void
    {
        $s = new IdSelector('foo');
        $e = Dom::createElement('div')->setAttribute('id', 'foo');
        static::assertTrue($s->matchWith($e));

        $e->setAttribute('id', 'bar');
        static::assertFalse($s->matchWith($e));
    }
}
