<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class ChildNodeMixinTest extends TestCase
{
    public function testAfter(): void
    {
        $oldParent = Dom::createDocument();
        $comment2 = Dom::createComment('2');
        $oldParent->append($comment2);
        static::assertEquals($oldParent, $comment2->parentNode());

        $parent = Dom::createDocument();
        $comment1 = Dom::createComment('1');
        $parent->append($comment1);
        $doctype = Dom::createDocumentType('html');
        $comment1->after($doctype, $comment2);

        static::assertCount(3, $parent->childNodes());
        static::assertSame($comment1, $parent->childNodes()->item(0));
        static::assertSame($doctype, $parent->childNodes()->item(1));
        static::assertSame($comment2, $parent->childNodes()->item(2));

        $div = Dom::createElement('div');
        $div->after($comment1); // should have no effect
        static::assertNull($div->nextSibling());
        static::assertSame($comment1, $parent->childNodes()->item(0));
        static::assertsame($parent, $comment1->parentNode());
    }

    public function testBefore(): void
    {
        $oldParent = Dom::createDocument();
        $div = Dom::createElement('div');
        $oldParent->append($div);
        static::assertEquals($oldParent, $div->parentNode());

        $parent = Dom::createDocumentFragment();
        $text1 = Dom::createText('1');
        $parent->append($text1);
        $text2 = Dom::createText('2');
        $text1->before($text2, $div);

        static::assertCount(3, $parent->childNodes());
        static::assertSame($text2, $parent->childNodes()->item(0));
        static::assertSame($div, $parent->childNodes()->item(1));
        static::assertSame($text1, $parent->childNodes()->item(2));
        static::assertEquals($parent, $div->parentNode());

        $main = Dom::createElement('main');
        $main->before($text1); // should have no effect
        static::assertNull($main->previousSibling());
        static::assertSame($text1, $parent->childNodes()->item(2));
        static::assertsame($parent, $text1->parentNode());
    }

    public function testRemove(): void
    {
        $parent = Dom::createDocument();
        $comment1 = Dom::createComment('1');
        $parent->append($comment1);
        $comment1->remove();
        static::assertCount(0, $parent->childNodes());
        static::assertNull($comment1->parentNode());
    }

    public function testReplaceWith(): void
    {
        $divA = Dom::createElement('div');
        $divA->setAttributeNode(Dom::createAttr('class', 'a'));
        $divB = Dom::createElement('div');
        $divB->setAttributeNode(Dom::createAttr('class', 'b'));
        $divC = Dom::createElement('div');
        $divC->setAttributeNode(Dom::createAttr('class', 'c'));

        $divA->appendChild($divB);
        $divB->appendChild($divC);

        $divA->replaceWith($divC); // should have no effect
        static::assertSame($divB, $divC->parentNode());
        static::assertSame($divA, $divB->parentNode());
        static::assertNull($divA->parentNode());

        $divB->replaceWith($divC);
        static::assertSame($divA, $divC->parentNode());
        static::assertNull($divB->parentNode());
    }
}
