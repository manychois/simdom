<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\CommentNode;
use Manychois\Simdom\Internal\DocFragNode;
use Manychois\Simdom\Internal\DocNode;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\Simdom\Internal\TextNode;
use Manychois\Simdom\Text;
use Manychois\SimdomTests\ExceptionTester;
use Manychois\SimdomTests\TestUtility;
use PHPUnit\Framework\TestCase;

class BaseParentNodeTest extends TestCase
{
    public function testChildElementCount(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $a2 = new ElementNode('a');
        $text3 = new TextNode('3');
        $i4 = new ElementNode('i');
        static::assertSame(0, $div->childElementCount());
        $div->append($comment1, $a2, $text3, $i4);
        static::assertSame(2, $div->childElementCount());
    }

    public function testFirstChild(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $a2 = new ElementNode('a');
        $text3 = new TextNode('3');
        $i4 = new ElementNode('i');
        static::assertNull($div->firstChild());
        $div->append($comment1, $a2, $text3, $i4);
        static::assertSame($comment1, $div->firstChild());
    }

    public function testFirstElementChild(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $a2 = new ElementNode('a');
        $text3 = new TextNode('3');
        $i4 = new ElementNode('i');
        static::assertNull($div->firstElementChild());
        $div->append($comment1, $a2, $text3, $i4);
        static::assertSame($a2, $div->firstElementChild());
    }

    public function testLastChild(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $a2 = new ElementNode('a');
        $text3 = new TextNode('3');
        $i4 = new ElementNode('i');
        static::assertNull($div->lastChild());
        $div->append($comment1, $a2, $text3, $i4);
        static::assertSame($i4, $div->lastChild());
    }

    public function testLastElementChild(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $a2 = new ElementNode('a');
        $text3 = new TextNode('3');
        $i4 = new ElementNode('i');
        static::assertNull($div->lastElementChild());
        $div->append($comment1, $a2, $text3, $i4);
        static::assertSame($i4, $div->lastElementChild());
    }

    public function testAppendChildUsingFragment(): void
    {
        $div = new ElementNode('div');
        $frag = new DocFragNode();
        $comment1 = new CommentNode('1');
        $a2 = new ElementNode('a');
        $text3 = new TextNode('3');
        $frag->append($comment1, $a2, $text3);
        $div->appendChild($frag);
        static::assertSame($comment1, $div->firstChild());
        static::assertSame($a2, $div->childNodes()->item(1));
        static::assertSame($text3, $div->lastChild());
        TestUtility::assertCount(0, $frag->childNodes());
    }

    public function testContains(): void
    {
        $div = new ElementNode('div');
        $div2 = new ElementNode('div');
        $div3 = new ElementNode('div');
        $div->appendChild($div2);
        $div2->appendChild($div3);
        static::assertTrue($div->contains($div));
        static::assertTrue($div->contains($div2));
        static::assertTrue($div->contains($div3));
        static::assertFalse($div3->contains($div2));
        static::assertFalse($div3->contains($div));
    }

    public function testDfs(): void
    {
        $div = new ElementNode('div');
        $div2 = new ElementNode('div');
        $div3 = new ElementNode('div');
        $div->append('[', $div2, ']');
        $div2->append('{', $div3, '}');
        $nodes = [];
        foreach ($div->dfs() as $node) {
            $nodes[] = $node;
        }
        TestUtility::assertCount(6, $nodes);
        $i = 0;
        $n = $nodes[$i++];
        static::assertTrue($n instanceof Text && $n->data() === '[');
        $n = $nodes[$i++];
        static::assertSame($div2, $n);
        $n = $nodes[$i++];
        static::assertTrue($n instanceof Text && $n->data() === '{');
        $n = $nodes[$i++];
        static::assertSame($div3, $n);
        $n = $nodes[$i++];
        static::assertTrue($n instanceof Text && $n->data() === '}');
        $n = $nodes[$i++];
        static::assertTrue($n instanceof Text && $n->data() === ']');
    }

    public function testDfsElements(): void
    {
        $div = new ElementNode('div');
        $div2 = new ElementNode('div');
        $div3 = new ElementNode('div');
        $div->append('[', $div2, ']');
        $div2->append('{', $div3, '}');
        $nodes = [];
        foreach ($div->dfsElements() as $node) {
            $nodes[] = $node;
        }
        TestUtility::assertCount(2, $nodes);
        $i = 0;
        $n = $nodes[$i++];
        static::assertSame($div2, $n);
        $n = $nodes[$i++];
        static::assertSame($div3, $n);
    }

    public function testHasChildNodes(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        static::assertFalse($div->hasChildNodes());
        $div->appendChild($comment1);
        static::assertTrue($div->hasChildNodes());
    }

    public function testInsertBefore(): void
    {
        $frag = new DocFragNode();
        $a = new ElementNode('a');
        $frag->appendChild($a);
        $one = new TextNode('1');
        $frag->insertBefore($one, $a);
        $i = new ElementNode('i');
        $frag->insertBefore($i, null);
        static::assertSame($frag, $one->parentNode());
        static::assertSame($one, $frag->firstChild());
        static::assertSame($a, $frag->childNodes()->item(1));
        static::assertSame($i, $frag->lastChild());

        $div = new ElementNode('div');
        $div->insertBefore($frag, null);
        static::assertSame($div, $one->parentNode());
        static::assertSame($one, $div->firstChild());
        static::assertSame($a, $div->childNodes()->item(1));
        static::assertSame($i, $div->lastChild());
        TestUtility::assertCount(0, $frag->childNodes());
    }

    public function testIsEqualNode(): void
    {
        $frag = Dom::createDocumentFragment();
        $doc = Dom::createDocument();
        static::assertFalse($frag->isEqualNode($doc));

        $frag2 = Dom::createDocumentFragment();
        static::assertTrue($frag->isEqualNode($frag2));

        $frag->append('1');
        $frag2->append('2');
        static::assertFalse($frag->isEqualNode($frag2));

        $frag2->replaceChildren('1');
        static::assertTrue($frag->isEqualNode($frag2));
    }

    public function testNormalize(): void
    {
        $div = new ElementNode('div');
        $innerDiv = new ElementNode('div');
        $div->prepend('3', $innerDiv, new TextNode(''));
        $div->prepend('1', '2', new CommentNode('!'));
        $innerDiv->append('3', '', '4');
        TestUtility::assertCount(6, $div->childNodes());
        TestUtility::assertCount(3, $innerDiv->childNodes());

        $div->normalize();

        TestUtility::assertCount(4, $div->childNodes());
        $n = $div->childNodes()->item(0);
        static::assertTrue($n instanceof Text && $n->data() === '12');
        $n = $div->childNodes()->item(1);
        static::assertTrue($n instanceof Comment && $n->data() === '!');
        $n = $div->childNodes()->item(2);
        static::assertTrue($n instanceof Text && $n->data() === '3');
        $n = $div->childNodes()->item(3);
        static::assertSame($innerDiv, $n);

        TestUtility::assertCount(1, $innerDiv->childNodes());
        $n = $innerDiv->childNodes()->item(0);
        static::assertTrue($n instanceof Text && $n->data() === '34');
    }

    public function testPrepend(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createElement('a');
        $div->append($a);
        static::assertSame($div, $a->parentNode());

        $p = Dom::createElement('p');
        $div->prepend($p);
        static::assertSame($div, $p->parentNode());
        static::assertSame($p, $div->firstChild());
        static::assertSame($a, $div->lastChild());

        $p->prepend($a);
        static::assertSame($p, $a->parentNode());
        static::assertSame($a, $p->firstChild());
        static::assertSame($p, $div->firstChild());
        static::assertSame($p, $div->lastChild());
    }

    public function testRemoveChild(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $div->appendChild($comment1);
        static::assertSame($div, $comment1->parentNode());
        $removed = $div->removeChild($comment1);
        static::assertNull($comment1->parentNode());
        TestUtility::assertCount(0, $div->childNodes());
        static::assertSame($comment1, $removed);
    }

    public function testRemoveChildIfWrongChildExpectsEx(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('The node is not a child of this node.'));
        $div = new ElementNode('div');
        $div->removeChild(new CommentNode('2'));
    }

    public function testReplaceChild(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $comment2 = new CommentNode('2');
        $div->appendChild($comment1);
        static::assertSame($div, $comment1->parentNode());
        $replaced = $div->replaceChild($comment2, $comment1);
        static::assertSame($div, $comment2->parentNode());
        static::assertNull($comment1->parentNode());
        TestUtility::assertCount(1, $div->childNodes());
        static::assertSame($comment1, $replaced);

        $frag = new DocFragNode();
        $frag->append('1', '2');
        $div->replaceChild($frag, $comment2);
        TestUtility::assertCount(2, $div->childNodes());
        $n = $div->childNodes()->item(0);
        static::assertTrue($n instanceof Text && $n->data() === '1');
        $n = $div->childNodes()->item(1);
        static::assertTrue($n instanceof Text && $n->data() === '2');
        TestUtility::assertCount(0, $frag->childNodes());
    }

    public function testReplaceChildren(): void
    {
        $div = new ElementNode('div');
        $a = new ElementNode('a');
        $div->append($a);

        $b = new ElementNode('b');
        $frag = new DocFragNode();
        $frag->replaceChildren('1', $b);
        $c = new ElementNode('c');

        $div->replaceChildren($frag, $c);
        TestUtility::assertCount(3, $div->childNodes());
        $n = $div->childNodes()->item(0);
        static::assertTrue($n instanceof Text && $n->data() === '1');
        $n = $div->childNodes()->item(1);
        static::assertSame($b, $n);
        $n = $div->childNodes()->item(2);
        static::assertSame($c, $n);
        TestUtility::assertCount(0, $frag->childNodes());
        static::assertNull($a->parentNode());
    }

    public function testSerialize(): void
    {
        $doc = new DocNode();
        $doc->append(
            Dom::createDocumentType('html'),
            Dom::createComment('test'),
            Dom::createElement('html'),
        );
        static::assertSame('<!DOCTYPE html><!--test--><html></html>', $doc->serialize());
    }

    #region Unusual node manipulation cases

    public function testInsertingRepeatedNodes(): void
    {
        $div = new ElementNode('div');
        $a = new ElementNode('a');
        $b = new ElementNode('b');
        $frag = new DocFragNode();
        $frag->append($a, $b);
        $div->append($a, $b, $frag, $b, $a);
        TestUtility::assertCount(2, $div->childNodes());
        static::assertSame($b, $div->childNodes()->item(0));
        static::assertSame($a, $div->childNodes()->item(1));
    }

    public function testInsertItselfExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $expected = new PreInsertionException($div, $div, null, 'A node cannot be its own child.');
        $fn = function () use ($div): void {
            $div->appendChild($div);
        };
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertItsAncestorExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div1 = new ElementNode('div');
        $div2 = new ElementNode('div');
        $div1->appendChild($div2);
        $div3 = new ElementNode('div');
        $div2->appendChild($div3);
        $a = new ElementNode('a');
        $div3->appendChild($a);

        $expected = new PreInsertionException($div3, $div1, $a, 'A child node cannot contain its own ancestor.');
        $fn = function () use ($div1, $div3, $a): void {
            $div3->insertBefore($div1, $a);
        };
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertDocExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $doc = new DocNode();
        $expected = new PreInsertionException($div, $doc, null, 'A document cannot be a child of another node.');
        $fn = function () use ($doc, $div): void {
            $div->appendChild($doc);
        };
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testInsertBeforeWrongRefExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $a = new ElementNode('a');
        $b = new ElementNode('b');
        $expected = new PreInsertionException($div, $a, $a, 'The reference child is not found in the parent node.');
        $fn = function () use ($div, $a, $b): void {
            $div->insertBefore($b, $a);
        };
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testReplaceChildWrongTargetExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $a = new ElementNode('a');
        $b = new ElementNode('b');
        $expected = new PreReplaceException($div, $a, $a, 'The node to be replaced is not found in the parent node.');
        $fn = function () use ($div, $a, $b): void {
            $div->replaceChild($b, $a);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceItselfExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $old = new ElementNode('a');
        $div->appendChild($old);
        $expected = new PreReplaceException($div, $div, $old, 'A node cannot be its own child.');
        $fn = function () use ($div, $old): void {
            $div->replaceChild($div, $old);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceItsAncestorExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div1 = new ElementNode('div');
        $div2 = new ElementNode('div');
        $div1->appendChild($div2);
        $div3 = new ElementNode('div');
        $div2->appendChild($div3);
        $a = new ElementNode('a');
        $div3->appendChild($a);

        $expected = new PreReplaceException($div3, $div1, $a, 'A child node cannot contain its own ancestor.');
        $fn = function () use ($div1, $div3, $a): void {
            $div3->replaceChild($div1, $a);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceDocExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $old = new ElementNode('a');
        $div->appendChild($old);
        $doc = new DocNode();
        $expected = new PreReplaceException($div, $doc, $old, 'A document cannot be a child of another node.');
        $fn = function () use ($doc, $div, $old): void {
            $div->replaceChild($doc, $old);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceChildrenWithItselfExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $expected = new PreReplaceException($div, $div, null, 'A node cannot be its own child.');
        $fn = function () use ($div): void {
            $div->replaceChildren($div);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceChildrenWithItsAncestorExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div1 = new ElementNode('div');
        $div2 = new ElementNode('div');
        $div1->appendChild($div2);
        $div3 = new ElementNode('div');
        $div2->appendChild($div3);
        $a = new ElementNode('a');
        $div3->appendChild($a);

        $expected = new PreReplaceException($div3, $div1, null, 'A child node cannot contain its own ancestor.');
        $fn = function () use ($div1, $div3): void {
            $div3->replaceChildren($div1);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceChildrenWithDocExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = new ElementNode('div');
        $doc = new DocNode();
        $expected = new PreReplaceException($div, $doc, null, 'A document cannot be a child of another node.');
        $fn = function () use ($div, $doc): void {
            $div->replaceChildren($doc);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    #endregion
}
