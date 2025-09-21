<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Cici\Matching\NodeType;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\Combinator;
use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Doctype;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Fragment;
use Manychois\Simdom\Internal\MatchContext;
use Manychois\Simdom\Text;
use Manychois\SimdomTests\AbstractBaseTestCase;
use RuntimeException;

/**
 * @internal
 *
 * @covers \Manychois\Simdom\Internal\MatchContext
 */
class MatchContextTest extends AbstractBaseTestCase
{
    private MatchContext $simpleContext;

    protected function setUp(): void
    {
        parent::setUp();
        $scope = Element::create('div');
        $this->simpleContext = new MatchContext(null, $scope, []);
    }

    public function testAreOfSameElementType(): void
    {
        $element1 = Element::create('div');
        $element2 = Element::create('div');
        $element3 = Element::create('span');
        $text = Text::create('test');

        $this->assertTrue($this->simpleContext->areOfSameElementType($element1, $element2));
        $this->assertFalse($this->simpleContext->areOfSameElementType($element1, $element3));
        $this->assertFalse($this->simpleContext->areOfSameElementType($element1, $text));
        $this->assertFalse($this->simpleContext->areOfSameElementType($text, $element1));
    }

    public function testGetAttributeValue(): void
    {
        $element = Element::create('input');
        $element->setAttr('type', 'text');
        $element->setAttr('name', 'username');

        $this->assertSame('text', $this->simpleContext->getAttributeValue($element, 'type'));
        $this->assertSame('username', $this->simpleContext->getAttributeValue($element, 'name'));
        $this->assertNull($this->simpleContext->getAttributeValue($element, 'nonexistent'));

        $text = Text::create('test');
        $this->assertNull($this->simpleContext->getAttributeValue($text, 'type'));
    }

    public function testGetAttributeValueWithWqName(): void
    {
        $element = Element::create('div');
        $element->setAttr('class', 'test');

        $wqName = new WqName(false, null, 'class');
        $this->assertSame('test', $this->simpleContext->getAttributeValue($element, $wqName));

        $wqNameWithPrefix = new WqName(true, 'prefix1', 'localName1');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prefix is not supported in Simdom. Use local name only.');
        $this->simpleContext->getAttributeValue($element, $wqNameWithPrefix);
    }

    public function testGetNodeType(): void
    {
        $this->assertSame(NodeType::Comment, $this->simpleContext->getNodeType(Comment::create('test')));
        $this->assertSame(NodeType::DocumentType, $this->simpleContext->getNodeType(Doctype::create('html')));
        $this->assertSame(NodeType::Document, $this->simpleContext->getNodeType(Document::create()));
        $this->assertSame(NodeType::Element, $this->simpleContext->getNodeType(Element::create('div')));
        $this->assertSame(NodeType::DocumentFragment, $this->simpleContext->getNodeType(Fragment::create()));
        $this->assertSame(NodeType::Text, $this->simpleContext->getNodeType(Text::create('test')));
    }

    public function testGetNodeTypeUnsupported(): void
    {
        $unsupportedNode = new class extends AbstractNode {
            public function clone(bool $deep = true): AbstractNode
            {
                throw new RuntimeException('Not implemented');
            }

            public function equals(AbstractNode $other): bool
            {
                return $this === $other;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported node type:');
        $this->simpleContext->getNodeType($unsupportedNode);
    }

    public function testGetParentNode(): void
    {
        $parent = Element::create('div');
        $child = Element::create('span');
        $parent->appendChild($child);

        $this->assertSame($parent, $this->simpleContext->getParentNode($child));

        $orphan = Element::create('p');
        $this->assertNull($this->simpleContext->getParentNode($orphan));
    }

    public function testGetRadioButtonGroup(): void
    {
        $form = Element::create('form');
        $radio1 = Element::create('input');
        $radio1->setAttr('type', 'radio');
        $radio1->setAttr('name', 'gender');
        $radio2 = Element::create('input');
        $radio2->setAttr('type', 'radio');
        $radio2->setAttr('name', 'gender');
        $radio3 = Element::create('input');
        $radio3->setAttr('type', 'radio');
        $radio3->setAttr('name', 'age');
        $textarea = Element::create('textarea'); // Non-radio input with the same name should be ignored
        $textarea->setAttr('name', 'gender');

        $form->appendChild($radio1);
        $form->appendChild($radio2);
        $form->appendChild($radio3);
        $form->appendChild($textarea);

        $group = $this->simpleContext->getRadioButtonGroup($radio1);
        $this->assertCount(2, $group);
        $this->assertContains($radio1, $group);
        $this->assertContains($radio2, $group);
        $this->assertNotContains($radio3, $group);

        $alone = Element::create('input');
        $alone->setAttr('type', 'radio');
        $alone->setAttr('name', 'alone');
        $group = $this->simpleContext->getRadioButtonGroup($alone);
        $this->assertCount(1, $group);
        $this->assertContains($alone, $group);
    }

    public function testGetRadioButtonGroupWithoutName(): void
    {
        $radio = Element::create('input');
        $radio->setAttr('type', 'radio');

        $group = $this->simpleContext->getRadioButtonGroup($radio);
        $this->assertSame([$radio], $group);
    }

    public function testGetRadioButtonGroupWithoutForm(): void
    {
        $document = Document::create();
        $radio1 = Element::create('input');
        $radio1->setAttr('type', 'radio');
        $radio1->setAttr('name', 'test');
        $radio2 = Element::create('input');
        $radio2->setAttr('type', 'radio');
        $radio2->setAttr('name', 'test');

        $document->appendChild($radio1);
        $document->appendChild($radio2);

        $group = $this->simpleContext->getRadioButtonGroup($radio1);
        $this->assertCount(2, $group);
    }

    public function testIsActuallyDisabled(): void
    {
        // Test disabled input
        $input = Element::create('input');
        $input->setAttr('disabled', '');
        $this->assertTrue($this->simpleContext->isActuallyDisabled($input));

        // Test enabled input
        $enabledInput = Element::create('input');
        $parent = Fragment::create();
        $parent->appendChild($enabledInput);
        $this->assertFalse($this->simpleContext->isActuallyDisabled($enabledInput));

        // Test disabled fieldset
        $fieldset = Element::create('fieldset');
        $fieldset->setAttr('disabled', '');
        $nestedInput = Element::create('input');
        $fieldset->appendChild($nestedInput);
        $this->assertTrue($this->simpleContext->isActuallyDisabled($nestedInput));

        // Test fieldset with legend
        $fieldsetWithLegend = Element::create('fieldset');
        $fieldsetWithLegend->setAttr('disabled', '');
        $legend = Element::create('legend');
        $inputInLegend = Element::create('input');
        $legend->appendChild($inputInLegend);
        $fieldsetWithLegend->appendChild($legend);
        $this->assertFalse($this->simpleContext->isActuallyDisabled($inputInLegend));

        // Test disabled optgroup
        $optgroup = Element::create('optgroup');
        $optgroup->setAttr('disabled', '');
        $this->assertTrue($this->simpleContext->isActuallyDisabled($optgroup));

        // Test option in disabled optgroup
        $disabledOptgroup = Element::create('optgroup');
        $disabledOptgroup->setAttr('disabled', '');
        $option = Element::create('option');
        $disabledOptgroup->appendChild($option);
        $this->assertTrue($this->simpleContext->isActuallyDisabled($option));

        // Test input in normal fieldset
        $fieldset = Element::create('fieldset');
        $input = Element::create('input');
        $fieldset->appendChild($input);
        $this->assertFalse($this->simpleContext->isActuallyDisabled($input));

        // Test disabled option
        $disabledOption = Element::create('option');
        $disabledOption->setAttr('disabled', '');
        $this->assertTrue($this->simpleContext->isActuallyDisabled($disabledOption));

        // Test non-HTML element
        $text = Text::create('test');
        $this->assertFalse($this->simpleContext->isActuallyDisabled($text));
    }

    public function testIsHtmlElement(): void
    {
        $div = Element::create('div');
        $span = Element::create('span');
        $text = Text::create('test');

        $this->assertTrue($this->simpleContext->isHtmlElement($div));
        $this->assertTrue($this->simpleContext->isHtmlElement($div, 'div'));
        $this->assertTrue($this->simpleContext->isHtmlElement($div, 'div', 'span'));
        $this->assertFalse($this->simpleContext->isHtmlElement($div, 'span'));
        $this->assertFalse($this->simpleContext->isHtmlElement($text));
        $this->assertFalse($this->simpleContext->isHtmlElement($text, 'div'));
    }

    public function testIsReadWritable(): void
    {
        // Test writable input
        $input = Element::create('input');
        $input->setAttr('type', 'text');
        $this->assertTrue($this->simpleContext->isReadWritable($input));

        // Test readonly input
        $readonlyInput = Element::create('input');
        $readonlyInput->setAttr('type', 'text');
        $readonlyInput->setAttr('readonly', '');
        $this->assertFalse($this->simpleContext->isReadWritable($readonlyInput));

        // Test disabled input
        $disabledInput = Element::create('input');
        $disabledInput->setAttr('type', 'text');
        $disabledInput->setAttr('disabled', '');
        $this->assertFalse($this->simpleContext->isReadWritable($disabledInput));

        // Test non-writable input types
        $checkboxInput = Element::create('input');
        $checkboxInput->setAttr('type', 'checkbox');
        $this->assertFalse($this->simpleContext->isReadWritable($checkboxInput));

        // Test textarea
        $textarea = Element::create('textarea');
        $this->assertTrue($this->simpleContext->isReadWritable($textarea));

        // Test readonly textarea
        $readonlyTextarea = Element::create('textarea');
        $readonlyTextarea->setAttr('readonly', '');
        $this->assertFalse($this->simpleContext->isReadWritable($readonlyTextarea));

        // Test contenteditable
        $contentEditable = Element::create('div');
        $contentEditable->setAttr('contenteditable', 'true');
        $this->assertTrue($this->simpleContext->isReadWritable($contentEditable));

        // Test element inside contenteditable
        $parent = Element::create('div');
        $parent->setAttr('contenteditable', 'true');
        $child = Element::create('span');
        $parent->appendChild($child);
        $this->assertTrue($this->simpleContext->isReadWritable($child));

        // Test contenteditable=false
        $notContentEditable = Element::create('div');
        $notContentEditable->setAttr('contenteditable', 'false');
        $this->assertFalse($this->simpleContext->isReadWritable($notContentEditable));
    }

    public function testLoopAncestors(): void
    {
        $grandparent = Element::create('div');
        $parent = Element::create('section');
        $child = Element::create('p');

        $grandparent->appendChild($parent);
        $parent->appendChild($child);

        $ancestors = iterator_to_array($this->simpleContext->loopAncestors($child, false));
        $this->assertCount(2, $ancestors);
        $this->assertSame($parent, $ancestors[0]);
        $this->assertSame($grandparent, $ancestors[1]);

        $ancestorsIncludingSelf = iterator_to_array($this->simpleContext->loopAncestors($child, true));
        $this->assertCount(3, $ancestorsIncludingSelf);
        $this->assertSame($child, $ancestorsIncludingSelf[0]);
    }

    public function testLoopChildren(): void
    {
        $parent = Element::create('div');
        $child1 = Element::create('span');
        $child2 = Element::create('p');
        $text = Text::create('text');

        $parent->appendChild($child1);
        $parent->appendChild($text);
        $parent->appendChild($child2);

        $children = iterator_to_array($this->simpleContext->loopChildren($parent));
        $this->assertCount(2, $children);
        $this->assertContains($child1, $children);
        $this->assertContains($child2, $children);
        $this->assertNotContains($text, $children);

        // Test with non-parent node
        $nonParent = Text::create('test');
        $this->assertEmpty(iterator_to_array($this->simpleContext->loopChildren($nonParent)));
    }

    public function testLoopDescendants(): void
    {
        $root = Element::create('div');
        $child = Element::create('span');
        $grandchild = Element::create('em');
        $text = Text::create('text');

        $root->appendChild($child);
        $child->appendChild($grandchild);
        $child->appendChild($text);

        $descendants = iterator_to_array($this->simpleContext->loopDescendants($root, false));
        $this->assertCount(3, $descendants);

        $descendantsIncludingSelf = iterator_to_array($this->simpleContext->loopDescendants($root, true));
        $this->assertCount(4, $descendantsIncludingSelf);
        $this->assertSame($root, $descendantsIncludingSelf[0]);
    }

    public function testLoopDescendantElements(): void
    {
        $root = Element::create('div');
        $child = Element::create('span');
        $grandchild = Element::create('em');
        $text = Text::create('text');

        $root->appendChild($child);
        $child->appendChild($grandchild);
        $child->appendChild($text);

        $elements = iterator_to_array($this->simpleContext->loopDescendantElements($root));
        $this->assertCount(2, $elements);
        $this->assertContains($child, $elements);
        $this->assertContains($grandchild, $elements);
        $this->assertNotContains($text, $elements);
    }

    public function testLoopLeftCandidatesDescendant(): void
    {
        $grandparent = Element::create('div');
        $parent = Element::create('section');
        $target = Element::create('p');

        $grandparent->appendChild($parent);
        $parent->appendChild($target);

        $candidates = iterator_to_array($this->simpleContext->loopLeftCandidates($target, Combinator::Descendant));
        $this->assertCount(2, $candidates);
        $this->assertSame($parent, $candidates[0]);
        $this->assertSame($grandparent, $candidates[1]);

        $parent = Document::create();
        $element = Element::create('div');
        $parent->appendChild($element);
        $candidates = iterator_to_array($this->simpleContext->loopLeftCandidates($element, Combinator::Descendant));
        $this->assertCount(0, $candidates);
    }

    public function testLoopLeftCandidatesChild(): void
    {
        $parent = Element::create('div');
        $target = Element::create('span');
        $parent->appendChild($target);

        $candidates = iterator_to_array($this->simpleContext->loopLeftCandidates($target, Combinator::Child));
        $this->assertCount(1, $candidates);
        $this->assertSame($parent, $candidates[0]);
    }

    public function testLoopLeftCandidatesNextSibling(): void
    {
        $parent = Element::create('div');
        $sibling = Element::create('span');
        $target = Element::create('p');

        $parent->appendChild($sibling);
        $parent->appendChild($target);

        $candidates = iterator_to_array($this->simpleContext->loopLeftCandidates($target, Combinator::NextSibling));
        $this->assertCount(1, $candidates);
        $this->assertSame($sibling, $candidates[0]);
    }

    public function testLoopLeftCandidatesSubsequentSibling(): void
    {
        $parent = Element::create('div');
        $sibling1 = Element::create('span');
        $sibling2 = Element::create('em');
        $target = Element::create('p');

        $parent->append($sibling1, $sibling2, $target);

        $candidates = iterator_to_array($this->simpleContext->loopLeftCandidates($target, Combinator::SubsequentSibling));
        $this->assertCount(2, $candidates);
        $this->assertSame($sibling2, $candidates[0]);
        $this->assertSame($sibling1, $candidates[1]);
    }

    public function testLoopLeftCandidatesUnsupportedCombinator(): void
    {
        $target = Element::create('div');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported combinator');
        iterator_to_array($this->simpleContext->loopLeftCandidates($target, Combinator::Column));
    }

    public function testLoopRightCandidates(): void
    {
        $parent = Element::create('div');
        $child1 = Element::create('span');
        $child2 = Text::create('text');
        $child3 = Element::create('p');

        $parent->append($child1, $child2, $child3);

        // Test Descendant
        $descendants = iterator_to_array($this->simpleContext->loopRightCandidates($parent, Combinator::Descendant));
        $this->assertCount(2, $descendants);

        // Test Child
        $children = iterator_to_array($this->simpleContext->loopRightCandidates($parent, Combinator::Child));
        $this->assertCount(2, $children);

        // Test NextSibling
        $nextSibling = iterator_to_array($this->simpleContext->loopRightCandidates($child1, Combinator::NextSibling));
        $this->assertCount(1, $nextSibling);
        $this->assertSame($child3, $nextSibling[0]);

        // Test SubsequentSibling
        $child3 = Element::create('em');
        $parent->appendChild($child3);
        $subsequentSiblings = iterator_to_array($this->simpleContext->loopRightCandidates($child1, Combinator::SubsequentSibling));
        $this->assertCount(2, $subsequentSiblings);
    }

    public function testLoopRightCandidatesUnsupportedCombinator(): void
    {
        $target = Element::create('div');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported combinator');
        iterator_to_array($this->simpleContext->loopRightCandidates($target, Combinator::Column));
    }

    public function testMatchElementType(): void
    {
        $div = Element::create('div');
        $text = Text::create('test');

        $divWqName = new WqName(false, null, 'div');
        $spanWqName = new WqName(false, null, 'span');
        $wildcardWqName = new WqName(false, null, '*');

        $this->assertTrue($this->simpleContext->matchElementType($div, $divWqName));
        $this->assertFalse($this->simpleContext->matchElementType($div, $spanWqName));
        $this->assertTrue($this->simpleContext->matchElementType($div, $wildcardWqName));
        $this->assertFalse($this->simpleContext->matchElementType($text, $divWqName));
    }

    public function testMatchElementTypeWithPrefix(): void
    {
        $div = Element::create('div');
        $wqNameWithPrefix = new WqName(true, 'prefix1', 'localName1');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prefix is not supported in Simdom. Use local name only.');
        $this->simpleContext->matchElementType($div, $wqNameWithPrefix);
    }

    public function testMatchDefaultNamespace(): void
    {
        $element = Element::create('div');
        $this->assertTrue($this->simpleContext->matchDefaultNamespace($element));
    }
}
