<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\UnitTests;

use Manychois\Simdom\Comment;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Text;
use PHPUnit\Framework\TestCase;

class AbstractNodeTest extends TestCase
{
    public function testAfter(): void
    {
        $div = new Element('div');
        $div->append('a', 'b', 'c');
        $a = $div->childNodeList->get(0);
        $b = $div->childNodeList->get(1);
        $c = $div->childNodeList->get(2);
        static::assertSame('abc', $div->allTextData());

        $a->after('x', 'y');
        static::assertSame('axybc', $div->allTextData());

        $b->after($c, $a, $c, $a);
        static::assertSame('xybca', $div->allTextData());
    }

    public function testAfterNoParent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This node has no parent.');
        $div = new Element('div');
        $div->after('a');
    }

    public function testAncestors(): void
    {
        $doc = new Document();
        $html = $doc->appendChild(new Element('html'));
        $head = $html->appendChild(new Element('head'));
        $title = $head->appendChild(new Element('title'));

        static::assertSame('head,html,#doc', self::debugPrintList($title->ancestors()));
    }

    public function testAppendChildOnNonParentNode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This node cannot have children.');
        $text = new Text('text');
        $text->appendChild(new Text('another'));
    }

    public function testBefore(): void
    {
        $div = new Element('div');
        $div->append('a', 'b', 'c');
        $a = $div->childNodeList->get(0);
        $b = $div->childNodeList->get(1);
        $c = $div->childNodeList->get(2);
        static::assertSame('abc', $div->allTextData());

        $a->before('x', 'y');
        static::assertSame('xyabc', $div->allTextData());

        $b->before($c, $b, $a);
        static::assertSame('xycba', $div->allTextData());
    }

    public function testBeforeNoParent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This node has no parent.');
        $div = new Element('div');
        $div->before('a');
    }

    public function testContains(): void
    {
        $doc = new Document();
        $html = $doc->appendChild(new Element('html'));
        $head = $html->appendChild(new Element('head'));
        $title = $head->appendChild(new Element('title'));
        $meta = $head->appendChild(new Element('meta'));

        static::assertTrue($doc->contains($title));
        static::assertTrue($head->contains($head));
        static::assertFalse($title->contains($meta));
    }

    public function testDetach(): void
    {
        $doc = new Document();
        $html = $doc->appendChild(new Element('html'));

        static::assertSame($doc, $html->parent());
        $html->detach();
        static::assertNull($html->parent());
        // no error
        $html->detach();
        static::assertNull($html->parent());
    }

    public function testIsFollowing(): void
    {
        $doc = new Document();
        $html = $doc->appendChild(new Element('html'));
        $head = $html->appendChild(new Element('head'));
        $title = $head->appendChild(new Element('title'));
        $body = $html->appendChild(new Element('body'));
        $div = $body->appendChild(new Element('div'));

        $anthorDoc = new Document();
        $anotherHtml = $anthorDoc->appendChild(new Element('html'));

        static::assertTrue($head->isFollowing($html));
        static::assertTrue($title->isFollowing($head));
        static::assertTrue($div->isFollowing($title));

        static::assertFalse($html->isFollowing($head));
        static::assertFalse($head->isFollowing($title));
        static::assertFalse($title->isFollowing($div));
        static::assertFalse($div->isFollowing($div));
        static::assertFalse($div->isFollowing($anotherHtml));
        static::assertFalse($anotherHtml->isFollowing($div));
    }

    public function testIsPreceding(): void
    {
        $doc = new Document();
        $html = $doc->appendChild(new Element('html'));
        $head = $html->appendChild(new Element('head'));
        $title = $head->appendChild(new Element('title'));
        $body = $html->appendChild(new Element('body'));
        $div = $body->appendChild(new Element('div'));

        $anthorDoc = new Document();
        $anotherHtml = $anthorDoc->appendChild(new Element('html'));

        static::assertFalse($head->isPreceding($html));
        static::assertFalse($title->isPreceding($head));
        static::assertFalse($div->isPreceding($title));
        static::assertFalse($div->isPreceding($div));
        static::assertFalse($div->isPreceding($anotherHtml));
        static::assertFalse($anotherHtml->isPreceding($div));

        static::assertTrue($html->isPreceding($head));
        static::assertTrue($head->isPreceding($title));
        static::assertTrue($title->isPreceding($div));
    }

    public function testNextElementSibling(): void
    {
        $div = new Element('div');
        $a = $div->appendChild('a');
        $b = $div->appendChild(new Element('b'));
        $c = $div->appendChild(new Comment('c'));
        $d = $div->appendChild(new Element('d'));
        $e = $div->appendChild('e');

        static::assertSame($b, $a->nextElementSibling());
        static::assertSame($d, $b->nextElementSibling());
        static::assertSame($d, $c->nextElementSibling());
        static::assertNull($d->nextElementSibling());
        static::assertNull($e->nextElementSibling());
        static::assertNull($div->nextElementSibling());
    }

    public function testNextSibling(): void
    {
        $div = new Element('div');
        $a = $div->appendChild('a');
        $b = $div->appendChild(new Element('b'));
        $c = $div->appendChild(new Comment('c'));

        static::assertSame($b, $a->nextSibling());
        static::assertSame($c, $b->nextSibling());
        static::assertNull($c->nextSibling());
        static::assertNull($div->nextSibling());
    }

    public function testParentElement(): void
    {
        $doc = new Document();
        $html = $doc->appendChild(new Element('html'));
        $head = $html->appendChild(new Element('head'));
        $title = $head->appendChild(new Element('title'));

        static::assertSame($head, $title->parentElement());
        static::assertSame($html, $head->parentElement());
        static::assertNull($html->parentElement());
    }

    public function testPrevElementSibling(): void
    {
        $div = new Element('div');
        $a = $div->appendChild('a');
        $b = $div->appendChild(new Element('b'));
        $c = $div->appendChild(new Comment('c'));
        $d = $div->appendChild(new Element('d'));
        $e = $div->appendChild('e');

        static::assertNull($a->prevElementSibling());
        static::assertNull($b->prevElementSibling());
        static::assertSame($b, $c->prevElementSibling());
        static::assertSame($b, $d->prevElementSibling());
        static::assertSame($d, $e->prevElementSibling());
        static::assertNull($div->prevElementSibling());
    }

    public function testPrevSibling(): void
    {
        $div = new Element('div');
        $a = $div->appendChild('a');
        $b = $div->appendChild(new Element('b'));
        $c = $div->appendChild(new Comment('c'));

        static::assertNull($a->prevSibling());
        static::assertSame($a, $b->prevSibling());
        static::assertSame($b, $c->prevSibling());
        static::assertNull($div->prevSibling());
    }

    public function testRoot(): void
    {
        $doc = new Document();
        $html = $doc->appendChild(new Element('html'));
        $head = $html->appendChild(new Element('head'));
        $title = $head->appendChild(new Element('title'));

        static::assertSame($doc, $title->root());
        static::assertSame($doc, $head->root());
        static::assertSame($doc, $html->root());
        static::assertSame($doc, $doc->root());
    }

    public function testIndexSequenceOnOrphan(): void
    {
        $div = new Element('div');
        $reflection = new \ReflectionClass($div);
        $indexSequence = $reflection->getMethod('indexSequence');
        $topmost = null;
        $result = $indexSequence->invokeArgs($div, [&$topmost]);
        static::assertSame([], $result);
        static::assertNull($topmost);
    }

    /**
     * @param iterable<\Manychois\Simdom\AbstractNode> $nodes
     */
    private static function debugPrintList(iterable $nodes): string
    {
        $debug = [];
        foreach ($nodes as $node) {
            if ($node instanceof Document) {
                $debug[] = '#doc';
            } elseif ($node instanceof Element) {
                $debug[] = $node->tagName;
            }
        }

        return \implode(',', $debug);
    }
}
