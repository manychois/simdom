<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\PreInsertionException;
use Manychois\Simdom\Internal\PreReplaceException;
use Manychois\SimdomTests\ExceptionTester;
use PHPUnit\Framework\TestCase;

class TextOnlyElementNodeTest extends TestCase
{
    public function testValidatePreInsertion(): void
    {
        $exHelper = new ExceptionTester();
        $script = Dom::createElement('script');
        $comment = Dom::createComment('comment');
        $expected = new PreInsertionException($script, $comment, null, 'Element script can only contain Text nodes.');
        $fn = function () use ($script, $comment): void {
            $script->append($comment);
        };
        $exHelper->expectPreInsertionException($fn, $expected);
    }

    public function testValidatePreReplace(): void
    {
        $exHelper = new ExceptionTester();
        $style = Dom::createElement('style');
        $text = Dom::createText('html, body { margin: 0; }');
        $style->append($text);
        $comment = Dom::createComment('comment');
        $expected = new PreReplaceException($style, $comment, $text, 'Element style can only contain Text nodes.');
        $fn = function () use ($style, $comment, $text): void {
            $style->replaceChild($comment, $text);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }

    public function testValidatePreReplaceAll(): void
    {
        $exHelper = new ExceptionTester();
        $title = Dom::createElement('title');
        $comment = Dom::createComment('comment');
        $expected = new PreReplaceException($title, $comment, null, 'Element title can only contain Text nodes.');
        $fn = function () use ($title, $comment): void {
            $title->replaceChildren($comment);
        };
        $exHelper->expectPreReplaceException($fn, $expected);
    }
}
