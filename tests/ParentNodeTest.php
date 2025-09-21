<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use InvalidArgumentException;
use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Fragment;
use Manychois\Simdom\Text;

use function Manychois\Simdom\e;

/**
 * @internal
 *
 * @coversNothing
 */
class ParentNodeTest extends AbstractBaseTestCase
{
    public function testAppendDocExpectsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot append a document node');
        $ele = Element::create('div');
        $ele->append(Document::create());
    }

    public function testAppendItselfExpectsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot append an ancestor node or itself');
        $ele = Element::create('div');
        $ele->append($ele);
    }

    public function testAppendChildDocExpectsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot append a document node');
        $ele = Element::create('div');
        $ele->appendChild(Document::create());
    }

    public function testAppendChildItselfExpectsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot append an ancestor node or itself');
        $ele = Element::create('div');
        $ele->appendChild($ele);
    }

    public function testAppendChildFragment(): void
    {
        $frag = Fragment::create();
        $frag->append('123');
        $ele = Element::create('div');
        $ele->appendChild($frag);
        $this->assertCount(1, $ele->childNodes);
        assert($ele->firstChild instanceof Text);
        $this->assertSame('123', $ele->firstChild->data);
    }

    public function testBfs(): void
    {
        $root = e('div', 'a odd', [
            e('div', 'b even', [
                e('span', 'c odd'),
                e('span', 'd even'),
            ]),
            e('div', 'e odd', [
                e('span', 'f even'),
                e('span', 'g odd'),
            ]),
        ]);

        $expected = $root->bfs(fn (AbstractNode $n) => $n instanceof Element && $n !== $root && $n->classList->contains('odd'));
        assert($expected instanceof Element);
        $this->assertSame('<div class="e odd">', $expected->openTagHtml);

        $expected = $root->bfs(fn (AbstractNode $n) => $n instanceof Text);
        $this->assertNull($expected);
    }

    public function testDescendantElements(): void
    {
        $root = e('div', [], [
            '123',
            e('div', 'a', [
                e('span', 'b'),
                '456',
                e('span', 'c'),
            ]),
            '789',
            e('div', 'd', [
                e('span', 'e'),
                '000',
                e('span', 'f'),
            ]),
        ]);
        $expected = [];
        foreach ($root->descendantElements() as $ele) {
            $expected[] = $ele->openTagHtml;
        }

        $this->assertEquals([
            '<div class="a">',
            '<span class="b">',
            '<span class="c">',
            '<div class="d">',
            '<span class="e">',
            '<span class="f">',
        ], $expected);
    }

    public function testDfs(): void
    {
        $root = e('div', 'a odd', [
            e('div', 'b even', [
                e('span', 'c odd'),
                e('span', 'd even'),
            ]),
            e('div', 'e odd', [
                e('span', 'f even'),
                e('span', 'g odd'),
            ]),
        ]);

        $expected = $root->dfs(fn (AbstractNode $n) => $n instanceof Element && $n->classList->contains('odd'));
        $this->assertSame($root, $expected);

        $expected = $root->dfs(fn (AbstractNode $n) => $n instanceof Element && $n !== $root && $n->classList->contains('odd'));
        assert($expected instanceof Element);
        $this->assertSame('<span class="c odd">', $expected->openTagHtml);

        $expected = $root->dfs(fn (AbstractNode $n) => $n instanceof Text);
        $this->assertNull($expected);
    }
}
