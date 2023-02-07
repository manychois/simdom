<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\AttrNode;
use PHPUnit\Framework\TestCase;

class AttrNodeTest extends TestCase
{
    public function testOwnerElementSet(): void
    {
        $div = Dom::createElement('div');
        $attr = new AttrNode('id');
        $attr->ownerElementSet($div);
        TestCase::assertSame($div, $attr->ownerElement());
        $attr->ownerElementSet($div);
        TestCase::assertSame($div, $attr->ownerElement());
    }
}
