<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\Simdom\Text;
use Manychois\SimdomTests\ExceptionTester;
use Manychois\SimdomTests\TestUtility;
use PHPUnit\Framework\TestCase;

class ElementNodeTest extends TestCase
{
    public function testClassName(): void
    {
        $e = Dom::createElement('div');
        static::assertSame('', $e->className());
        $e->classNameSet('  a   b   c  ');
        static::assertSame('  a   b   c  ', $e->className());
        TestUtility::assertCount(3, $e->classList());
        static::assertSame('a', $e->classList()->item(0));
        static::assertSame('b', $e->classList()->item(1));
        static::assertSame('c', $e->classList()->item(2));
        static::assertSame('a b c', $e->classList()->value());
    }

    public function testId(): void
    {
        $e = Dom::createElement('div');
        static::assertSame('', $e->id());
        $e->idSet('abc');
        static::assertSame('abc', $e->id());
        static::assertSame('abc', $e->getAttribute('id'));
        $e->setAttribute('id', 'def');
        static::assertSame('def', $e->id());
    }

    public function testInnerHTMLOnVoidElement(): void
    {
        $br = Dom::createElement('br');
        $br->append('123');
        static::assertSame('', $br->innerHTML());
    }

    public function testInnerHTMLSetOnDiv(): void
    {
        $div = Dom::createElement('div');
        $div->innerHTMLSet('<p><b>Testing</b></p>');
        static::assertSame('<p><b>Testing</b></p>', $div->innerHTML());
    }

    public function testInnerHTMLSetOnScript(): void
    {
        $script = Dom::createElement('script');
        $script->innerHTMLSet('console.log("A & B");');
        static::assertSame('console.log("A & B");', $script->innerHTML());
    }

    public function testNextElementSibling(): void
    {
        $div = Dom::createElement('div');
        static::assertNull($div->nextElementSibling());
        $parent = Dom::createDocumentFragment();
        $parent->append($div);
        static::assertNull($div->nextElementSibling());
        $parent->append('text');
        static::assertNull($div->nextElementSibling());
        $a = Dom::createElement('a');
        $parent->append($a);
        static::assertSame($a, $div->nextElementSibling());
    }

    public function testOuterHTML(): void
    {
        $form = Dom::createElement('form');
        $form->setAttribute('disabled', '');
        $form->setAttribute('action', 'https://example.com');
        $input = Dom::createElement('input');
        $input->setAttribute('type', 'text');
        $input->setAttribute('name', 'name');
        $input->setAttribute('value', '');
        $form->append($input);
        $output = '<form disabled action="https://example.com"><input type="text" name="name" value=""></form>';
        static::assertSame($output, $form->outerHTML());
    }

    public function testOuterHTMLSetOnRootEleExpectsEx(): void
    {
        $doc = Dom::createDocument();
        $html = Dom::createElement('html');
        $doc->append($html);
        $ex = new InvalidArgumentException('Root element cannot be modified via outerHTMLSet().');
        $this->expectExceptionObject($ex);
        $html->outerHTMLSet('<html><body></body></html>');
    }

    public function testOuterHTMLSet(): void
    {
        $frag = Dom::createDocumentFragment();
        $div = Dom::createElement('div');
        $div->outerHTMLSet('<p>Testing</p>'); // Do nothing if its parent is null.
        static::assertSame('<div></div>', $div->outerHTML());
        $frag->append($div);
        static::assertSame('<div></div>', $frag->firstElementChild()->outerHTML());
        $div->outerHTMLSet('<p>Testing</p>');
        $p = $frag->firstElementChild();
        static::assertSame('P', $p->tagName());
        static::assertSame('Testing', $p->textContent());
        static::assertNotSame($div, $p);
        static::assertNull($div->parentNode());
        $p->outerHTMLSet('Just text');
        static::assertTrue($frag->firstChild() instanceof Text);
        static::assertSame('Just text', $frag->firstChild()->textContent());
        static::assertNull($p->parentNode());

        $div->append('[', $p, ']');
        $p->outerHTMLSet('<img src="logo.png"><br/>');
        static::assertSame('<div>[<img src="logo.png"><br>]</div>', $div->outerHTML());
    }

    public function testPreviousElementSibling(): void
    {
        $div = Dom::createElement('div');
        static::assertNull($div->previousElementSibling());
        $parent = Dom::createDocumentFragment();
        $parent->append($div);
        static::assertNull($div->previousElementSibling());
        $parent->append('text');
        static::assertNull($div->previousElementSibling());
        $a = Dom::createElement('a');
        $parent->append($a);
        static::assertSame($div, $a->previousElementSibling());
        $parent->insertBefore(Dom::createComment('comment'), $div);
        static::assertNull($div->previousElementSibling());
    }

    public function testGetAttributeNames(): void
    {
        $div = Dom::createElement('div');
        $div->idSet('div-1');
        $div->classList()->add('test');
        static::assertSame('id,class', implode(',', $div->getAttributeNames()));
    }

    public function testGetAttributeNode(): void
    {
        $div = Dom::createElement('div');
        $attr = Dom::createAttr('id', 'div-1');
        $div->setAttributeNode($attr);
        static::assertSame($attr, $div->getAttributeNode('id'));
    }

    public function testGetAttributeNodeNS(): void
    {
        $div = Dom::createElement('div');
        $div->innerHTMLSet('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $svg = $div->firstElementChild();
        $attr = $svg->getAttributeNodeNS(DomNs::XmlNs, 'xmlns');
        static::assertSame('http://www.w3.org/2000/svg', $attr->value());
    }

    public function testGetAttributeNS(): void
    {
        $div = Dom::createElement('div');
        $div->innerHTMLSet('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $svg = $div->firstElementChild();
        static::assertSame('http://www.w3.org/2000/svg', $svg->getAttributeNS(DomNs::XmlNs, 'xmlns'));
    }

    public function testHasAttribute(): void
    {
        $div = Dom::createElement('div');
        static::assertFalse($div->hasAttribute('id'));
        $div->idSet('div-1');
        static::assertTrue($div->hasAttribute('id'));
    }

    public function testHasAttributeNS(): void
    {
        $div = Dom::createElement('div');
        $div->innerHTMLSet('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $svg = $div->firstElementChild();
        static::assertFalse($svg->hasAttributeNS(null, 'xmlns'));
        static::assertTrue($svg->hasAttributeNS(DomNs::XmlNs, 'xmlns'));
    }

    public function testHasAttributes(): void
    {
        $div = Dom::createElement('div');
        static::assertFalse($div->hasAttributes());
        $div->idSet('div-1');
        static::assertTrue($div->hasAttributes());
    }

    public function testRemoveAttribute(): void
    {
        $div = Dom::createElement('div');
        $div->classNameSet('a');
        $attr = $div->getAttributeNode('class');
        static::assertSame('a', $attr->value());
        static::assertSame('a', $div->classList()->value());

        $div->removeAttribute('class');
        static::assertFalse($div->hasAttributes());
        static::assertSame('', $div->classList()->value());
        static::assertNull($attr->ownerElement());
    }

    public function testRemoveAttributeNode(): void
    {
        $div = Dom::createElement('div');
        $attr = Dom::createAttr('id', 'div-1');
        $div->setAttributeNode($attr);
        $div->removeAttributeNode($attr);
        static::assertFalse($div->hasAttributes());
    }

    public function testRemoveNotFoundAttributeNodeExpectsEx(): void
    {
        $div = Dom::createElement('div');
        $attr = Dom::createAttr('id', 'div-1');
        $ex = new InvalidArgumentException('The attribute is not owned by this element.');
        $this->expectExceptionObject($ex);
        $div->removeAttributeNode($attr);
    }

    public function testRemoveAttributeNS(): void
    {
        $div = Dom::createElement('div');
        $div->innerHTMLSet('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $svg = $div->firstElementChild();
        $svg->removeAttributeNS(DomNs::XmlNs, 'xmlns');
        static::assertFalse($svg->hasAttributes());
    }

    public function testSetAttributeNS(): void
    {
        $div = Dom::createElement('div');
        $div->setAttributeNS(DomNs::XmlNs, 'xmlns', Domns::Html->value);
        static::assertSame('<div xmlns="http://www.w3.org/1999/xhtml"></div>', $div->outerHTML());
    }

    public function testSetAttrPrefixWithoutNamespaceExpectsEx(): void
    {
        $div = Dom::createElement('div');
        $ex = new InvalidArgumentException('Namespace must be specified when prefix is specified.');
        $this->expectExceptionObject($ex);
        $div->setAttributeNS(null, 'a:b', 'c');
    }

    public function testSetXmlAttrWithInvalidPrefixExpectsEx(): void
    {
        $div = Dom::createElement('div');
        $ex = new InvalidArgumentException('Expects XML namespace for prefix xml.');
        $this->expectExceptionObject($ex);
        $div->setAttributeNS(DomNs::XmlNs, 'xml:b', 'c');
    }

    public function testSetXmlnsAttrWithInvalidNsExpectsEx(): void
    {
        $div = Dom::createElement('div');
        $ex = new InvalidArgumentException('Invalid namespace for XMLNS.');
        $this->expectExceptionObject($ex);
        $div->setAttributeNS(DomNs::Xml, 'xmlns', 'c');
    }

    public function testSetXmlnsAttrWithInvalidQnameExpectsEx(): void
    {
        $div = Dom::createElement('div');
        $ex = new InvalidArgumentException('Expects prefix xmlns for XMLNS namespace.');
        $this->expectExceptionObject($ex);
        $div->setAttributeNS(DomNs::XmlNs, 'smlns:b', 'c');
    }

    public function testToggleAttribute(): void
    {
        $div = Dom::createElement('div');
        $present = $div->toggleAttribute('id');
        static::assertTrue($div->hasAttribute('id'));
        static::assertTrue($present);
        static::assertSame('', $div->id());
        $present = $div->toggleAttribute('id');
        static::assertFalse($div->hasAttribute('id'));
        static::assertFalse($present);
        $present = $div->toggleAttribute('id', false);
        static::assertFalse($div->hasAttribute('id'));
        static::assertFalse($present);
        $div->idSet('1');
        $present = $div->toggleAttribute('id', true);
        static::assertTrue($div->hasAttribute('id'));
        static::assertTrue($present);
        static::assertSame('1', $div->id());
    }

    public function testTextContentSet(): void
    {
        $div = Dom::createElement('div');
        $div->textContentSet('test');
        $n = $div->firstChild();
        static::assertTrue($n instanceof Text && $n->data() === 'test');
    }

    public function testCloneNode(): void
    {
        $div = Dom::createElement('div');
        $div->idSet('div-1');
        $div->classList()->add('test');
        $p = Dom::createElement('p');
        $comment = Dom::createComment('comment');
        $p->append($comment);
        $div->append($p);

        $clone = $div->cloneNode(true);
        static::assertNotSame($div, $clone);
        static::assertSame('<div id="div-1" class="test"><p><!--comment--></p></div>', $clone->outerHTML());
        static::assertNotSame($p, $clone->firstElementChild());
        static::assertNotSame($comment, $clone->firstElementChild()->firstChild());
    }

    public function testIsEqualNode(): void
    {
        $a = Dom::createElement('div');
        $b = Dom::createElement('div');
        static::assertTrue($a->isEqualNode($b));
        $c = Dom::createElement('p');
        static::assertFalse($a->isEqualNode($c));
        $a->idSet('div-1');
        static::assertFalse($a->isEqualNode($b));
        $b->idSet('div-2');
        static::assertFalse($a->isEqualNode($b));
        $b->idSet('div-1');
        static::assertTrue($a->isEqualNode($b));
        $pa = Dom::createElement('p');
        $a->append($pa);
        static::assertFalse($a->isEqualNode($b));
        $pb = Dom::createElement('p');
        $b->append($pb);
        static::assertTrue($a->isEqualNode($b));
    }

    #region Unusual node manipulation cases

    public function testInsertDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = Dom::createElement('div');
        $doctype = Dom::createDocumentType('html', '', '');
        $expected = new PreInsertionException($div, $doctype, null, 'DocumentType cannot be a child of an Element.');
        $fn = fn () => $div->appendChild($doctype);
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testReplaceWithDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = Dom::createElement('div');
        $a = Dom::createElement('a');
        $div->append($a);
        $doctype = Dom::createDocumentType('html', '', '');
        $expected = new PreReplaceException($div, $doctype, $a, 'DocumentType cannot be a child of an Element.');
        $fn = fn () => $div->replaceChild($doctype, $a);
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testReplaceAllContainingDoctypeExpectsEx(): void
    {
        $exHelper = new ExceptionTester();
        $div = Dom::createElement('div');
        $doctype = Dom::createDocumentType('html', '', '');
        $expected = new PreReplaceException($div, $doctype, null, 'DocumentType cannot be a child of an Element.');
        $fn = fn () => $div->replaceChildren('1', $doctype, '2');
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    #endregion
}
