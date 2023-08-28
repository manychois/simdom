<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use Manychois\Simdom\Dom;
use Manychois\Simdom\NodeType;
use PHPUnit\Framework\TestCase;

class DoctypeNodeTest extends TestCase
{
    public function testNodeType(): void
    {
        $doctype = Dom::createDocumentType();
        static::assertSame(NodeType::DocumentType, $doctype->nodeType());
    }
}
