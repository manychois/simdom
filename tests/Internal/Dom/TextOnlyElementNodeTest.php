<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class TextOnlyElementNodeTest extends TestCase
{
    public function testAppendExpectsException(): void
    {
        $style = Dom::createElement('style');
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('Element <style> can have child text nodes only.');
        $style->append(Dom::createComment());
    }

    public function testReplaceExpectsException(): void
    {
        $script = Dom::createElement('script');
        $text = Dom::createText('console.log("Hello, world!");');
        $script->append($text);
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('Element <script> can have child text nodes only.');
        $script->replace($text, Dom::createComment());
    }

    public function testReplace(): void
    {
        $script = Dom::createElement('script');
        $text = Dom::createText('console.log("Hello, world!");');
        $script->append($text);
        $script->replace($text, 'window.a = 1;');
        /** @var \Manychois\Simdom\TextInterface $text */
        $text = $script->firstChild();
        static::assertSame('window.a = 1;', $text->data());
    }
}
