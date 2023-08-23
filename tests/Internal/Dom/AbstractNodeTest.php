<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class AbstractNodeTest extends TestCase
{
    public function testNextSibling(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createElement('a');
        $b = Dom::createText('b');
        $c = Dom::createComment('c');
        $div->append($a, $b, $c);

        static::assertSame($b, $a->nextSibling());
        static::assertSame($c, $b->nextSibling());
        static::assertNull($c->nextSibling());
    }
}
