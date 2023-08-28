<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use Manychois\Simdom\NodeType;
use PHPUnit\Framework\TestCase;

class DocFragmentNodeTest extends TestCase
{
    public function testNodeType(): void
    {
        $f = Dom::createDocumentFragment();
        static::assertSame(NodeType::DocumentFragment, $f->nodeType());
    }

    public function testAppendException(): void
    {
        $f = Dom::createDocumentFragment();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DocumentType cannot be a child of a DocumentFragment.');
        $f->append(Dom::createDocumentType());
    }

    public function testReplaceException(): void
    {
        $f = Dom::createDocumentFragment();
        $a = Dom::createElement('a');
        $f->append($a);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DocumentType cannot be a child of a DocumentFragment.');
        $f->replace($a, Dom::createDocumentType());
    }
}
