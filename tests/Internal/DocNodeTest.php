<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\SimdomTests\ExceptionTester;
use PHPUnit\Framework\TestCase;

class DocNodeTest extends TestCase
{
    public function testBody(): void
    {
        $doc = Dom::createDocument();
        static::assertNull($doc->body());
        $html = Dom::createElement('html');
        $doc->append($html);
        static::assertNull($doc->body());
        $head = Dom::createElement('head');
        $body = Dom::createElement('body');
        $html->append($head, $body);
        static::assertSame($body, $doc->body());
    }

    public function testDoctype(): void
    {
        $doc = Dom::createDocument();
        static::assertNull($doc->doctype());
        $doctype = Dom::createDocumentType('html');
        $doc->append(Dom::createComment('abc'), $doctype);
        static::assertSame($doctype, $doc->doctype());
    }

    public function testHead(): void
    {
        $doc = Dom::createDocument();
        static::assertNull($doc->head());
        $html = Dom::createElement('html');
        $doc->append($html);
        static::assertNull($doc->head());
        $head = Dom::createElement('head');
        $body = Dom::createElement('body');
        $html->append($head, $body);
        static::assertSame($head, $doc->head());
    }

    public function testCloneNode(): void
    {
        $doc = Dom::createDocument();
        static::assertNull($doc->head());
        $html = Dom::createElement('html');
        $doc->append($html);
        static::assertNull($doc->head());
        $head = Dom::createElement('head');
        $body = Dom::createElement('body');
        $html->append($head, $body);

        $doc2 = $doc->cloneNode(true);
        static::assertNotSame($doc, $doc2);
        static::assertNotSame($doc->documentElement(), $doc2->documentElement());
        static::assertNotSame($doc->head(), $doc2->head());
        static::assertNotSame($doc->body(), $doc2->body());
        static::assertSame('<html><head></head><body></body></html>', $doc2->documentElement()->outerHTML());
    }

    public function testTextContent(): void
    {
        $doc = Dom::createDocument();
        static::assertNull($doc->head());
        $html = Dom::createElement('html');
        $doc->append($html);
        static::assertNull($doc->head());
        $head = Dom::createElement('head');
        $body = Dom::createElement('body');
        $html->append($head, $body);

        static::assertNull($doc->textContent());
        $doc->textContentSet('abc');
        static::assertNull($doc->textContent());
        static::assertSame('<html><head></head><body></body></html>', $doc->documentElement()->outerHTML());
    }

    #region Unusual node manipulation cases

    public function testInsertTextExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $text = Dom::createText('');
        $expected = new PreInsertionException($doc, $text, null, 'Text cannot be a child of a Document.');
        $fn = fn () => $doc->appendChild($text);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertTwoElementsExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $html1 = Dom::createElement('html');
        $html2 = Dom::createElement('html');
        $expected = new PreInsertionException($doc, $html2, null, 'Document can have only 1 root Element.');
        $fn = fn () => $doc->append($html1, $html2);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertElementToDocWithRootElementExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $doc->append($html);
        $expected = new PreInsertionException($doc, $html, null, 'Document can have only 1 root Element.');
        $fn = fn () => $doc->appendChild($html);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertElementBeforeDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $doctype = Dom::createDocumentType('html');
        $doc->append($doctype);
        $expected = new PreInsertionException(
            $doc,
            $html,
            $doctype,
            'DocumentType must be before Element in a Document.'
        );
        $fn = fn () => $doc->insertBefore($html, $doctype);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertDoctypeAfterElementExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $doctype = Dom::createDocumentType('html');
        $doc->append($html);
        $expected = new PreInsertionException(
            $doc,
            $doctype,
            null,
            'DocumentType must be before Element in a Document.'
        );
        $fn = fn () => $doc->appendChild($doctype);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertDoctypeToDocWithDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $doctype1 = Dom::createDocumentType('html');
        $doctype2 = Dom::createDocumentType('html');
        $doc->append($doctype1);
        $expected = new PreInsertionException(
            $doc,
            $doctype2,
            null,
            'Document can have only 1 DocumentType.'
        );
        $fn = fn () => $doc->appendChild($doctype2);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertTwoDoctypesExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $doctype1 = Dom::createDocumentType('html');
        $doctype2 = Dom::createDocumentType('html');
        $expected = new PreInsertionException(
            $doc,
            $doctype2,
            null,
            'Document can have only 1 DocumentType.'
        );
        $fn = fn () => $doc->append($doctype1, $doctype2);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertElementAndDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $doctype = Dom::createDocumentType('html');
        $expected = new PreInsertionException(
            $doc,
            $doctype,
            null,
            'DocumentType must be before Element in a Document.'
        );
        $fn = fn () => $doc->append($html, $doctype);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testReplaceWithTextExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $text = Dom::createText('');
        $html = Dom::createElement('html');
        $doc->append($html);
        $expected = new PreReplaceException($doc, $text, $html, 'Text cannot be a child of a Document.');
        $fn = fn () => $doc->replaceChild($text, $html);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceCommentWithElementInDocWithRootElementExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $comment = Dom::createComment('placeholder');
        $html = Dom::createElement('html');
        $doc->append($html, $comment);
        $html2 = Dom::createElement('html');
        $expected = new PreReplaceException(
            $doc,
            $html2,
            $comment,
            'Document can have only 1 root Element.'
        );
        $fn = fn () => $doc->replaceChild($html2, $comment);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceCommentWithElementResultsInElementBeforeDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $comment = Dom::createComment('placeholder');
        $doctype = Dom::createDocumentType('html');
        $html = Dom::createElement('html');
        $doc->append($comment, $doctype);
        $expected = new PreReplaceException(
            $doc,
            $html,
            $comment,
            'DocumentType must be before Element in a Document.'
        );
        $fn = fn () => $doc->replaceChild($html, $comment);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceWithTwoElementsExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $doctype = Dom::createDocumentType('html');
        $doc->append($doctype);

        $frag = Dom::createDocumentFragment();
        $html = Dom::createElement('html');
        $html2 = Dom::createElement('html');
        $frag->append($html, $html2);

        $expected = new PreReplaceException(
            $doc,
            $html2,
            $doctype,
            'Document can have only 1 root Element.'
        );
        $fn = fn () => $doc->replaceChild($frag, $doctype);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceCommentWithDoctypeResultsInDoctypeAfterElementExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $comment = Dom::createComment('placeholder');
        $doctype = Dom::createDocumentType('html');
        $html = Dom::createElement('html');
        $doc->append($html, $comment);
        $expected = new PreReplaceException(
            $doc,
            $doctype,
            $comment,
            'DocumentType must be before Element in a Document.'
        );
        $fn = fn () => $doc->replaceChild($doctype, $comment);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testRepalceCommentWithDoctypeToDocWithDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $doctype1 = Dom::createDocumentType('html');
        $doc->append($doctype1);
        $comment = Dom::createComment('placeholder');
        $doctype2 = Dom::createDocumentType('html');
        $doc->append($comment);
        $expected = new PreReplaceException(
            $doc,
            $doctype2,
            $comment,
            'Document can have only 1 DocumentType.'
        );
        $fn = fn () => $doc->replaceChild($doctype2, $comment);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceChildrenWithTextExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $text = Dom::createText('');
        $expected = new PreReplaceException($doc, $text, null, 'Text cannot be a child of a Document.');
        $fn = fn () => $doc->replaceChildren($text);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceChildrenWithTwoElementsExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $html2 = Dom::createElement('html');
        $expected = new PreReplaceException(
            $doc,
            $html2,
            null,
            'Document can have only 1 root Element.'
        );
        $fn = fn () => $doc->replaceChildren($html, $html2);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceChildrenWithElementDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $doctype = Dom::createDocumentType('html');
        $html = Dom::createElement('html');
        $expected = new PreReplaceException(
            $doc,
            $doctype,
            null,
            'DocumentType must be before Element in a Document.'
        );
        $fn = fn () => $doc->replaceChildren($html, $doctype);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceChildrenWithTwoDoctypesExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $doc = Dom::createDocument();
        $doctype1 = Dom::createDocumentType('html');
        $doctype2 = Dom::createDocumentType('html');
        $expected = new PreReplaceException(
            $doc,
            $doctype2,
            null,
            'Document can have only 1 DocumentType.'
        );
        $fn = fn () => $doc->replaceChildren($doctype1, $doctype2);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    #endregion
}
