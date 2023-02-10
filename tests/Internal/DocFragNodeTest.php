<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\Simdom\Node;
use Manychois\SimdomTests\ExceptionTester;
use PHPUnit\Framework\TestCase;

class DocFragNodeTest extends TestCase
{
    public function testCloneNode(): void
    {
        $frag1 = Dom::createDocumentFragment();
        $frag1->appendChild(Dom::createElement('div'));
        $frag2 = $frag1->cloneNode(true);
        static::assertNotSame($frag1, $frag2);
        static::assertNotSame($frag1->firstChild(), $frag2->firstChild());
        static::assertSame('<div></div>', $frag2->firstElementChild()->outerHTML());
    }

    public function testNodeType(): void
    {
        $frag = Dom::createDocumentFragment();
        static::assertSame(Node::DOCUMENT_FRAGMENT_NODE, $frag->nodeType());
    }

    #region Unusual node manipulation cases

    public function testInsertDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $frag = Dom::createDocumentFragment();
        $doctype = Dom::createDocumentType('html', '', '');
        $expected = new PreInsertionException(
            $frag,
            $doctype,
            null,
            'DocumentType cannot be a child of a DocumentFragment.'
        );
        $fn = function () use ($frag, $doctype): void {
            $frag->appendChild($doctype);
        };
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testReplaceWithDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $frag = Dom::createDocumentFragment();
        $a = Dom::createElement('a');
        $frag->append($a);
        $doctype = Dom::createDocumentType('html', '', '');
        $expected = new PreReplaceException(
            $frag,
            $doctype,
            $a,
            'DocumentType cannot be a child of a DocumentFragment.'
        );
        $fn = function () use ($frag, $doctype, $a): void {
            $frag->replaceChild($doctype, $a);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceAllContainingDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $frag = Dom::createDocumentFragment();
        $doctype = Dom::createDocumentType('html', '', '');
        $expected = new PreReplaceException(
            $frag,
            $doctype,
            null,
            'DocumentType cannot be a child of a DocumentFragment.'
        );
        $fn = function () use ($frag, $doctype): void {
            $frag->replaceChildren('1', $doctype, '2');
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    #endregion
}
