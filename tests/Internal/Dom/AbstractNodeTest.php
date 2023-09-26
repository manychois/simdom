<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class AbstractNodeTest extends TestCase
{
    public function testIndex(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createElement('a');
        $b = Dom::createText('b');
        $c = Dom::createComment('c');
        $div->append($a, $b, $c);

        static::assertSame(-1, $div->index());
        static::assertSame(0, $a->index());
        static::assertSame(1, $b->index());
        static::assertSame(2, $c->index());
    }

    public function testNextElement(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createElement('a');
        $b = Dom::createText('b');
        $c = Dom::createComment('c');
        $d = Dom::createElement('d');
        $div->append($a, $b, $c, $d);

        static::assertNull($div->nextElement());
        static::assertSame($d, $a->nextElement());
        static::assertSame($d, $b->nextElement());
        static::assertSame($d, $c->nextElement());
        static::assertNull($d->nextElement());
    }

    public function testNextNode(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createElement('a');
        $b = Dom::createText('b');
        $c = Dom::createComment('c');
        $div->append($a, $b, $c);

        static::assertNull($div->nextNode());
        static::assertSame($b, $a->nextNode());
        static::assertSame($c, $b->nextNode());
        static::assertNull($c->nextNode());
    }

    public function testParentElement(): void
    {
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $head = Dom::createElement('head');
        $doc->append($html);
        $html->append($head);

        static::assertNull($doc->parentElement());
        static::assertNull($html->parentElement());
        static::assertSame($html, $head->parentElement());
    }

    public function testPrevElement(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createText('a');
        $b = Dom::createElement('b');
        $c = Dom::createComment('c');
        $d = Dom::createElement('d');
        $div->append($a, $b, $c, $d);

        static::assertNull($div->prevElement());
        static::assertNull($a->prevElement());
        static::assertNull($b->prevElement());
        static::assertSame($b, $c->prevElement());
        static::assertSame($b, $d->prevElement());
    }

    public function testPrevNode(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createText('a');
        $b = Dom::createElement('b');
        $c = Dom::createComment('c');
        $d = Dom::createElement('d');
        $div->append($a, $b, $c, $d);

        static::assertNull($div->prevNode());
        static::assertNull($a->prevNode());
        static::assertSame($a, $b->prevNode());
        static::assertSame($b, $c->prevNode());
        static::assertSame($c, $d->prevNode());
    }

    public function testRootNode(): void
    {
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $head = Dom::createElement('head');
        $title = Dom::createElement('title');
        $text = Dom::createText('Testing');
        $doc->append($html);
        $html->append($head);
        $head->append($title);
        $title->append($text);

        static::assertNull($doc->rootNode());
        static::assertSame($doc, $html->rootNode());
        static::assertSame($doc, $head->rootNode());
        static::assertSame($doc, $title->rootNode());
        static::assertSame($doc, $text->rootNode());
    }
}
