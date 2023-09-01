<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use Generator;
use InvalidArgumentException;
use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\NodeInterface;
use Manychois\Simdom\NodeType;
use Manychois\Simdom\ParentNodeInterface;
use Manychois\Simdom\TextInterface;
use PHPUnit\Framework\TestCase;

class AbstractParentNodeTest extends TestCase
{
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
        $parent = Dom::createElement('div');
        $child = Dom::createElement('div');
        $ref = Dom::createElement('div');
        yield [$parent, $child, $ref, 'The reference child is not found in the parent node.'];
        yield [$parent, $parent, null, 'A node cannot be its own child.'];
        $parent->append($child);
        yield [$child, $parent, null, 'A child node cannot contain its own ancestor.'];
        $doc = Dom::createDocument();
        yield [$parent, $doc, null, 'A document cannot be a child of any node.'];
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
        $a = Dom::createElement('a');
        $b = Dom::createElement('b');
        $c = Dom::createElement('c');
        yield [$a, $b, $c, 'The node to be replaced is not found in the parent node.'];
        $a = Dom::createElement('a');
        $b = Dom::createElement('b');
        $a->append($b);
        $b->append($c);
        yield [$a, $b, $a, 'A node cannot be its own child.'];
        yield [$b, $c, $a, 'A child node cannot contain its own ancestor.'];
        $doc = Dom::createDocument();
        yield [$a, $b, $doc, 'A document cannot be a child of another node.'];
    }

    public function testFastAppend(): void
    {
        $a = new ElementNode('a');
        $b = new ElementNode('b');
        $a->fastAppend($b);
        static::assertSame($b, $a->firstChild());
        static::assertSame($a, $b->parentNode());

        $c = new ElementNode('c');
        $c->fastAppend($b);
        static::assertSame($b, $c->firstChild());
        static::assertSame($c, $b->parentNode());
        static::assertNull($a->firstChild());
    }

    public function testAppend(): void
    {
        $a = Dom::createElement('a');
        $b = Dom::createText('b');
        $a->append($b);
        static::assertSame($b, $a->firstChild());
        static::assertSame($a, $b->parentNode());

        $c = Dom::createElement('c');
        $c->append($b);
        static::assertSame($b, $c->firstChild());
        static::assertSame($c, $b->parentNode());
        static::assertNull($a->firstChild());
    }

    public function testChildElementCount(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createComment('a');
        $b = Dom::createElement('b');
        $c = Dom::createText('c');
        $d = Dom::createElement('d');
        $e = Dom::createComment('e');
        $div->append($a, $b, $c, $d, $e);
        static::assertEquals(2, $div->childElementCount());
    }

    public function testChildNodeAt(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createComment('a');
        $b = Dom::createElement('b');
        $c = Dom::createText('c');

        $div->append($a, $b, $c);
        static::assertNull($div->childNodeAt(-1));
        static::assertSame($a, $div->childNodeAt(0));
        static::assertSame($b, $div->childNodeAt(1));
        static::assertSame($c, $div->childNodeAt(2));
        static::assertNull($div->childNodeAt(3));
    }

    public function testContains(): void
    {
        $f = Dom::createDocumentFragment();
        $a = Dom::createElement('a');
        $b = Dom::createElement('b');
        $c = Dom::createElement('c');
        $f->append($a, $b);
        $b->append($c);

        static::assertTrue($f->contains($a));
        static::assertTrue($f->contains($b));
        static::assertTrue($f->contains($c));
        static::assertFalse($a->contains($c));
        static::assertTrue($b->contains($c));
        static::assertTrue($c->contains($c));
    }

    public function testFind(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createComment('a');
        $b = Dom::createElement('b');
        $c = Dom::createText('c');

        $div->append($a, $b, $c);
        $found = $div->find(function (NodeInterface $node, int $index) use ($a, $b, $c): bool {
            switch ($index) {
                case 0:
                    static::assertSame($a, $node);
                    break;
                case 1:
                    static::assertSame($b, $node);
                    break;
                case 2:
                    static::assertSame($c, $node);

                    return true;
            }

            return false;
        });
        static::assertSame($c, $found);

        $found = $div->find(fn (NodeInterface $node) => $node->nodeType() === NodeType::DocumentFragment);
        static::assertNull($found);
    }

    public function testFirstElementChild(): void
    {
        $div = Dom::createElement('div');
        static::assertNull($div->firstElementChild());

        $a = Dom::createComment('a');
        $b = Dom::createElement('b');
        $c = Dom::createText('c');
        $d = Dom::createElement('d');
        $e = Dom::createComment('e');
        $div->append($a, $b, $c, $d, $e);
        static::assertSame($b, $div->firstElementChild());
    }

    public function testInsertBefore(): void
    {
        $div = Dom::createElement('div');
        $a = Dom::createComment('a');
        $b = Dom::createElement('b');
        $c = Dom::createText('c');
        $d = Dom::createComment('d');

        $b->append($a);
        $f = Dom::createDocumentFragment();
        $f->insertBefore(null, $c);
        $f->insertBefore($c, $b, $a, $b);

        static::assertEquals(3, iterator_count($f->childNodes()));
        static::assertSame($a, $f->childNodeAt(0));
        static::assertSame($b, $f->childNodeAt(1));
        static::assertSame($c, $f->childNodeAt(2));
        static::assertNull($b->firstChild());

        $div->append($d);
        $div->insertBefore($d, 'First', $c, $f);
        static::assertEquals(5, iterator_count($div->childNodes()));
        $n = $div->childNodeAt(0);
        static::assertTrue($n instanceof TextInterface);
        /** @var TextInterface $n */
        static::assertSame('First', $n->data());
        static::assertSame($a, $div->childNodeAt(1));
        static::assertSame($b, $div->childNodeAt(2));
        static::assertSame($c, $div->childNodeAt(3));
        static::assertSame($d, $div->childNodeAt(4));
    }

    public function testLasttElementChild(): void
    {
        $div = Dom::createElement('div');
        static::assertNull($div->lastElementChild());

        $a = Dom::createComment('a');
        $b = Dom::createElement('b');
        $c = Dom::createText('c');
        $d = Dom::createElement('d');
        $e = Dom::createComment('e');
        $div->append($a, $b, $c, $d, $e);
        static::assertSame($d, $div->lastElementChild());
    }

    public function testPrepend(): void
    {
        $a = Dom::createElement('a');
        $b = Dom::createText('b');
        $c = Dom::createElement('c');
        $a->prepend($b);
        $a->prepend($c);
        static::assertSame($c, $a->firstChild());
        static::assertSame($b, $a->lastChild());
        static::assertSame($a, $b->parentNode());
        static::assertSame($a, $c->parentNode());

        $f = Dom::createDocumentFragment();
        $f->prepend($a, $b, $c);

        static::assertSame($a, $f->firstChild());
        static::assertSame($b, $f->childNodeAt(1));
        static::assertSame($c, $f->lastChild());
        static::assertSame($f, $a->parentNode());
        static::assertSame($f, $b->parentNode());
        static::assertSame($f, $c->parentNode());
    }

    public function testRemoveChild(): void
    {
        $doc = Dom::createDocument();
        $a = Dom::createComment('a');
        $b = Dom::createComment('b');
        $c = Dom::createComment('c');
        $doc->append($a, $b, $c);

        $removed = $doc->removeChild($b);
        static::assertTrue($removed);
        static::assertSame($a, $doc->firstChild());
        static::assertSame($c, $doc->lastChild());
        static::assertNull($b->parentNode());

        $removed = $doc->removeChild($b);
        static::assertFalse($removed);
        static::assertSame($a, $doc->firstChild());
        static::assertSame($c, $doc->lastChild());
    }

    public function testReplace(): void
    {
        $f = Dom::createDocumentFragment();
        $a = Dom::createElement('a');
        $f->append($a);
        $b = Dom::createElement('b');
        $c = Dom::createElement('c');
        $f->replace($a, $b, $c);

        static::assertSame($b, $f->firstChild());
        static::assertSame($c, $f->lastChild());
        static::assertNull($a->parentNode());

        $d = Dom::createElement('d');
        $a->append($d);
        $a->replace($d, $f);
        static::assertSame($b, $a->firstChild());
        static::assertSame($c, $a->lastChild());
        static::assertNull($d->parentNode());
        static::assertEquals(0, iterator_count($f->childNodes()));
    }
}
