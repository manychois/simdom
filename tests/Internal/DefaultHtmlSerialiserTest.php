<?php

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Comment;
use Manychois\Simdom\Element;
use Manychois\Simdom\Fragment;
use Manychois\Simdom\Internal\DefaultHtmlSerialiser;
use Manychois\SimdomTests\AbstractBaseTestCase;

class DefaultHtmlSerialiserTest extends AbstractBaseTestCase
{
    public function testSerialiseFragment(): void
    {
        $fragment = Fragment::create();
        $fragment->append(
            Element::create('div'),
            Comment::create('comment'),
            'Text'
        );

        $s = new DefaultHtmlSerialiser();
        $html = $s->serialise($fragment);
        $expected = '<div></div><!--comment-->Text';
        self::assertSame($expected, $html);
    }

    public function testSerialiseElement(): void
    {
        $div = Element::create('div');
        $div->append('Some text');
        $div->setAttr('data1', '\'');
        $div->setAttr('data2', '"');
        $div->setAttr('data3', '\'"');

        $s = new DefaultHtmlSerialiser();
        $html = $s->serialise($div);
        $expected = '<div data1="\'" data2=\'"\' data3="\'&quot;">Some text</div>';
        self::assertSame($expected, $html);
    }
}