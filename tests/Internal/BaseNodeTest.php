<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Internal\BaseNode;
use Manychois\Simdom\Internal\CommentNode;
use Manychois\Simdom\Internal\DocFragNode;
use Manychois\Simdom\Internal\DocNode;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Internal\TextNode;
use PHPUnit\Framework\TestCase;

class BaseNodeTest extends TestCase
{
    public function testNextSibling(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $text2 = new TextNode('2');
        $a3 = new ElementNode('a');
        $div->append($comment1, $text2, $a3);
        static::assertSame($text2, $comment1->nextSibling());
        static::assertSame($a3, $text2->nextSibling());
        static::assertNull($a3->nextSibling());
        static::assertNull($div->nextSibling());
    }

    public function testOnwerDocument(): void
    {
        $html = new ElementNode('html');
        static::assertNull($html->ownerDocument());
        $doc = new DocNode();
        $doc->appendChild($html);
        static::assertSame($doc, $html->ownerDocument());
        static::assertNull($doc->ownerDocument());
        $body = new ElementNode('body');
        $html->appendChild($body);
        static::assertSame($doc, $body->ownerDocument());
    }

    public function testParentElement(): void
    {
        $frag = new DocFragNode();
        $div1 = new ElementNode('div');
        $div2 = new ElementNode('div');
        $frag->appendChild($div1);
        $div1->appendChild($div2);
        static::assertSame($div1, $div2->parentElement());
        static::assertNull($div1->parentElement());
        static::assertNull($frag->parentElement());
    }

    public function testParentNode(): void
    {
        $frag = new DocFragNode();
        $div1 = new ElementNode('div');
        $div2 = new ElementNode('div');
        $frag->appendChild($div1);
        $div1->appendChild($div2);
        static::assertSame($div1, $div2->parentNode());
        static::assertSame($frag, $div1->parentNode());
        static::assertNull($frag->parentNode());
    }

    public function testPreviousSibling(): void
    {
        $div = new ElementNode('div');
        $comment1 = new CommentNode('1');
        $text2 = new TextNode('2');
        $a3 = new ElementNode('a');
        $div->append($comment1, $text2, $a3);
        static::assertNull($comment1->previousSibling());
        static::assertSame($comment1, $text2->previousSibling());
        static::assertSame($text2, $a3->previousSibling());
        static::assertNull($div->previousSibling());
    }

    public function testGetRootNode(): void
    {
        $doc = new DocNode();
        $html = new ElementNode('html');
        $doc->appendChild($html);
        $body = new ElementNode('body');
        $html->appendChild($body);
        $div = new ElementNode('div');
        $body->appendChild($div);
        static::assertSame($doc, $doc->getRootNode());
        static::assertSame($doc, $html->getRootNode());
        static::assertSame($doc, $body->getRootNode());
        static::assertSame($doc, $div->getRootNode());
    }

    public function testEscapeString(): void
    {
        static::assertSame('&quot;1&quot; < &quot;2&quot;', BaseNode::escapeString('"1" < "2"', true));
    }
}
