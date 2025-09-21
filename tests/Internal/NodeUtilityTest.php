<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Fragment;
use Manychois\Simdom\Internal\NodeUtility;
use Manychois\Simdom\Text;
use Manychois\SimdomTests\AbstractBaseTestCase;

/**
 * @internal
 *
 * @covers \Manychois\Simdom\Internal\NodeUtility
 */
class NodeUtilityTest extends AbstractBaseTestCase
{
    public function testConvertToDistinctNodesWithnothing(): void
    {
        $result = NodeUtility::convertToDistinctNodes();
        $this->assertSame([], $result);
    }

    public function testConvertToDistinctNodesWithSingleNode(): void
    {
        $element = Element::create('div');
        $result = NodeUtility::convertToDistinctNodes($element);
        $this->assertSame([$element], $result);
    }

    public function testConvertToDistinctNodesWithDistinctNodes(): void
    {
        $element1 = Element::create('div');
        $element2 = Element::create('span');
        $text = Text::create('test');

        $result = NodeUtility::convertToDistinctNodes($element1, $element2, $text);

        $this->assertCount(3, $result);
        $this->assertSame($element1, $result[0]);
        $this->assertSame($element2, $result[1]);
        $this->assertSame($text, $result[2]);
    }

    public function testConvertToDistinctNodesWithDuplicates(): void
    {
        $element1 = Element::create('div');
        $element2 = Element::create('span');
        $text = Text::create('test');

        $result = NodeUtility::convertToDistinctNodes($element1, $element2, $element1, $text, $element2, $text);

        $this->assertCount(3, $result);
        $this->assertSame($element1, $result[0]);
        $this->assertSame($element2, $result[1]);
        $this->assertSame($text, $result[2]);
    }

    public function testConvertToDistinctNodesWithDifferentNodeTypes(): void
    {
        $element = Element::create('div');
        $text = Text::create('content');
        $comment = Comment::create('comment');
        $fragment = Fragment::create();
        $insideText = Text::create('inside');
        $fragment->append($insideText);
        $result = NodeUtility::convertToDistinctNodes($element, $text, $comment, $fragment);

        $this->assertCount(4, $result);
        $this->assertSame($element, $result[0]);
        $this->assertSame($text, $result[1]);
        $this->assertSame($comment, $result[2]);
        $this->assertSame($insideText, $result[3]);
    }

    public function testConvertDocumentNodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Document node is not allowed');

        $document = Document::create();
        NodeUtility::convertToDistinctNodes($document);
    }

    public function testConvertToDistinctNodesPreservesOrder(): void
    {
        $element1 = Element::create('div');
        $element2 = Element::create('span');
        $element3 = Element::create('p');

        // will return the last occurrence
        $result = NodeUtility::convertToDistinctNodes($element2, $element1, $element3, $element1, $element2);

        $this->assertCount(3, $result);
        $this->assertSame($element3, $result[0]);
        $this->assertSame($element1, $result[1]);
        $this->assertSame($element2, $result[2]);
    }

    public function testConvertToDistinctNodesWithLargeArray(): void
    {
        $nodes = [];
        $uniqueNodes = [];

        // Create 100 unique nodes and 100 duplicates
        for ($i = 0; $i < 10; ++$i) {
            $element = Element::create("div{$i}");
            $uniqueNodes[] = $element;

            // Add each node multiple times
            for ($j = 0; $j < 10; ++$j) {
                $nodes[] = $element;
            }
        }

        $result = NodeUtility::convertToDistinctNodes(...$nodes);

        $this->assertCount(10, $result);
        for ($i = 0; $i < 10; ++$i) {
            $this->assertSame($uniqueNodes[$i], $result[$i]);
        }
    }

    public function testConvertToDistinctNodesWithNestedStructure(): void
    {
        $parent = Element::create('div');
        $child1 = Element::create('span');
        $child2 = Element::create('p');

        $parent->append($child1, $child2);

        // Include parent and children with duplicates
        $result = NodeUtility::convertToDistinctNodes($parent, $child1, $child2, $parent, $child1);

        $this->assertCount(3, $result);
        $this->assertSame($child2, $result[0]);
        $this->assertSame($parent, $result[1]);
        $this->assertSame($child1, $result[2]);
    }
}
