<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\ParentNodeInterface;
use Manychois\Simdom\Dom;
use Manychois\Simdom\NodeInterface;
use Manychois\Simdom\NodeType;
use PHPUnit\Framework\TestCase;

class DocNodeTest extends TestCase
{
    public function testNodeType(): void
    {
        $f = Dom::createDocument();
        static::assertSame(NodeType::Document, $f->nodeType());
    }

    public function testAppend(): void
    {
        $doc = Dom::createDocument();
        $doctype = Dom::createDocumentType();
        $html = Dom::createElement('html');
        $comment = Dom::createComment('comment');
        $doc->append($doctype, $html, $comment);
        static::assertSame($doctype, $doc->firstChild());
        static::assertSame($html, $doc->childNodeAt(1));
        static::assertSame($comment, $doc->lastChild());
    }

    public function testReplace(): void
    {
        $doc = Dom::createDocument();
        $doctype = Dom::createDocumentType();
        $html = Dom::createElement('html');
        $html->setAttribute('id', 'html-1');        
        $doc->append($doctype, $html);

        $newDoctype = Dom::createDocumentType('html5');
        $newHtml = Dom::createElement('html');
        $newHtml->setAttribute('id', 'html-2');

        $doc->replace($doctype, $newDoctype);
        $doc->replace($html, $newHtml);

        static::assertSame($newDoctype, $doc->firstChild());
        static::assertSame($newHtml, $doc->lastChild());

        static::assertNull($doctype->parentNode());
        static::assertNull($html->parentNode());
    }

    /**
     * @dataProvider provideAppendExpectsException
     */
    public function testAppendExpectsException(array $children, string $expectedMessage): void
    {
        $doc = Dom::createDocument();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $doc->append(...$children);
    }

    public static function provideAppendExpectsException(): Generator
    {
        $doctype1 = Dom::createDocumentType();
        $doctype2 = Dom::createDocumentType();
        yield [[$doctype1, $doctype2], 'Document can have only 1 DocumentType.'];
        yield [[Dom::createElement('html'), $doctype1], 'DocumentType must be before Element in a Document.'];
    }

    /**
     * @dataProvider provideValidatePreInsertion
     */
    public function testValidatePreInsertion(
        ParentNodeInterface $parent,
        NodeInterface $child,
        ?NodeInterface $ref,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $parent->insertBefore($ref, $child);
    }

    public static function provideValidatePreInsertion(): Generator
    {
        $doc = Dom::createDocument();
        $f = Dom::createDocumentFragment();
        $f->append(Dom::createElement('a'), Dom::createElement('b'));
        yield [$doc, Dom::createText(''), null, 'Text cannot be a child of a Document.'];
        yield [$doc, $f, null, 'Document can have only 1 root Element.'];

        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $doc->append($html);
        yield [$doc, Dom::createElement('head'), $html, 'Document can have only 1 root Element.'];
        yield [$doc, Dom::createDocumentType(), null, 'DocumentType must be before Element in a Document.'];

        $doc = Dom::createDocument();
        $doctype = Dom::createDocumentType();
        $doc->append($doctype);
        yield [$doc, Dom::createElement('html'), $doctype, 'DocumentType must be before Element in a Document.'];
        yield [$doc, Dom::createDocumentType(), $doctype, 'Document can have only 1 DocumentType.'];
    }

    /**
     * @dataProvider provideValidatePreReplace
     */
    public function testValidatePreReplace(
        ParentNodeInterface $parent,
        NodeInterface $old,
        NodeInterface $new,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $parent->replace($old, $new);
    }

    public static function provideValidatePreReplace(): Generator
    {
        $doc = Dom::createDocument();
        $comment = Dom::createComment('comment');
        $doc->append($comment);
        yield [$doc, $comment, Dom::createText(''), 'Text cannot be a child of a Document.'];

        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $comment = Dom::createComment('comment');
        $doc->append($html, $comment);
        yield [$doc, $comment, Dom::createElement('head'), 'Document can have only 1 root Element.'];
        yield [$doc, $comment, Dom::createDocumentType(), 'DocumentType must be before Element in a Document.'];

        $doc = Dom::createDocument();
        $comment = Dom::createComment('comment');
        $doctype = Dom::createDocumentType();
        $doc->append($comment, $doctype);
        yield [$doc, $comment, Dom::createElement('html'), 'DocumentType must be before Element in a Document.'];
    }

    /**
     * @dataProvider provideReplaceExpectsException
     */
    public function testReplaceExpectsException(array $children, string $expectedMessage): void
    {
        $doc = Dom::createDocument();
        $comment = Dom::createComment('comment');
        $doc->append($comment);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $doc->replace($comment, ...$children);
    }

    public static function provideReplaceExpectsException(): Generator
    {
        $doctype1 = Dom::createDocumentType();
        $doctype2 = Dom::createDocumentType();
        yield [[$doctype1, $doctype2], 'Document can have only 1 DocumentType.'];
        $a = Dom::createElement('a');
        $b = Dom::createElement('b');
        yield [[$a, $b], 'Document can have only 1 root Element.'];
    }
}
