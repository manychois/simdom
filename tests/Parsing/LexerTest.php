<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Parsing;

use Manychois\Simdom\DomNs;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Parsing\CommentToken;
use Manychois\Simdom\Parsing\DoctypeToken;
use Manychois\Simdom\Parsing\EofToken;
use Manychois\Simdom\Parsing\Lexer;
use Manychois\Simdom\Parsing\StringToken;
use Manychois\Simdom\Parsing\TagToken;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
    public function testConsumeRcDataText(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);

        $lexer->setInput('<title><b>&amp;</b></title>', 7);
        static::assertEquals('<b>&</b>', $lexer->consumeRcDataText('title'));
    }

    public function testConsumeRawText(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);

        $lexer->setInput('<script>// &amp;</script>', 8);
        static::assertEquals('// &amp;', $lexer->consumeRawText('script'));

        $lexer->setInput('<script>// &amp;</script/>text', 8);
        static::assertEquals('// &amp;', $lexer->consumeRawText('script'));

        $lexer->setInput('<script>// &amp;</script', 8);
        static::assertEquals('// &amp;</script', $lexer->consumeRawText('script'));
    }

    public function testConsumeBogusComment(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);
        $parser->stack->push(new ElementNode('html'));

        $lexer->setInput('<?php // not working ?>', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(CommentToken::class, $token);
        if ($token instanceof CommentToken) {
            static::assertEquals('?php // not working ?', $token->value);
        }

        $parser->emitted = [];
        $lexer->setInput('<![CDATA[ text', 0);
        $lexer->stepTokenize();
        static::assertCount(2, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(CommentToken::class, $token);
        if ($token instanceof CommentToken) {
            static::assertEquals('[CDATA[ text', $token->value);
        }
        $token = $parser->emitted[1];
        static::assertInstanceOf(EofToken::class, $token);
    }

    public function testConsumeCdata(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);
        $parser->stack->push(new ElementNode('svg', DomNs::Svg));

        $lexer->setInput('<![CDATA[ text ]]>', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(StringToken::class, $token);
        if ($token instanceof StringToken) {
            static::assertEquals(' text ', $token->value);
        }

        $parser->emitted = [];
        $lexer->setInput('<![CDATA[ text', 0);
        $lexer->stepTokenize();
        static::assertCount(2, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(StringToken::class, $token);
        if ($token instanceof StringToken) {
            static::assertEquals(' text', $token->value);
        }
        $token = $parser->emitted[1];
        static::assertInstanceOf(EofToken::class, $token);
    }

    public function testConsumeComment(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);

        $lexer->setInput('<!-->', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(CommentToken::class, $token);
        if ($token instanceof CommentToken) {
            static::assertEquals('', $token->value);
        }

        $parser->emitted = [];
        $lexer->setInput('<!--<a>-->', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(CommentToken::class, $token);
        if ($token instanceof CommentToken) {
            static::assertEquals('<a>', $token->value);
        }

        $parser->emitted = [];
        $lexer->setInput('<!-- ', 0);
        $lexer->stepTokenize();
        static::assertCount(2, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(CommentToken::class, $token);
        if ($token instanceof CommentToken) {
            static::assertEquals(' ', $token->value);
        }
        $token = $parser->emitted[1];
        static::assertInstanceOf(EofToken::class, $token);
    }

    public function testConsumeDoctype(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);

        $lexer->setInput('<!doctype', 0);
        $lexer->stepTokenize();
        static::assertCount(2, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(DoctypeToken::class, $token);
        if ($token instanceof DoctypeToken) {
            static::assertEquals(null, $token->name);
            static::assertEquals(null, $token->publicId);
            static::assertEquals(null, $token->systemId);
        }
        $token = $parser->emitted[1];
        static::assertInstanceOf(EofToken::class, $token);

        $parser->emitted = [];
        $html = <<<'HTML'
<!DOCTYPE HTML PUBLIC
    "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">
HTML;
        $lexer->setInput($html, 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(DoctypeToken::class, $token);
        if ($token instanceof DoctypeToken) {
            static::assertEquals('html', $token->name);
            static::assertEquals('-//W3C//DTD HTML 4.01//EN', $token->publicId);
            static::assertEquals('http://www.w3.org/TR/html4/strict.dtd', $token->systemId);
        }

        $parser->emitted = [];
        $lexer->setInput('<!DOCTYPE math SYSTEM "http://www.w3.org/Math/DTD/mathml1/mathml.dtd">', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(DoctypeToken::class, $token);
        if ($token instanceof DoctypeToken) {
            static::assertEquals('math', $token->name);
            static::assertEquals(null, $token->publicId);
            static::assertEquals('http://www.w3.org/Math/DTD/mathml1/mathml.dtd', $token->systemId);
        }
    }

    public function testConsumeEndTag(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);

        $lexer->setInput('</div>', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(TagToken::class, $token);
        if ($token instanceof TagToken) {
            static::assertEquals(false, $token->isStartTag);
            static::assertEquals('div', $token->name);
            static::assertCount(0, $token->attributes);
        }

        $parser->emitted = [];
        $lexer->setInput('</div attr="1" />', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(TagToken::class, $token);
        if ($token instanceof TagToken) {
            static::assertEquals(false, $token->isStartTag);
            static::assertEquals('div', $token->name);
            static::assertCount(0, $token->attributes);
        }

        $parser->emitted = [];
        $lexer->setInput('</>', 0);
        $lexer->stepTokenize();
        static::assertCount(0, $parser->emitted);

        $parser->emitted = [];
        $lexer->setInput('</', 0);
        $lexer->stepTokenize();
        static::assertCount(2, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(StringToken::class, $token);
        if ($token instanceof StringToken) {
            static::assertEquals('</', $token->value);
        }
        $token = $parser->emitted[1];
        static::assertInstanceOf(EofToken::class, $token);

        $parser->emitted = [];
        $lexer->setInput('</123>', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(CommentToken::class, $token);
        if ($token instanceof CommentToken) {
            static::assertEquals('123', $token->value);
        }
    }

    public function testConsumeStartTag(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);

        $lexer->setInput('<input type="text" readonly />', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(TagToken::class, $token);
        if ($token instanceof TagToken) {
            static::assertEquals(true, $token->isStartTag);
            static::assertEquals(true, $token->isSelfClosing);
            static::assertEquals('input', $token->name);
            static::assertCount(2, $token->attributes);
            static::assertEquals('text', $token->attributes['type']);
            static::assertEquals('', $token->attributes['readonly']);
        }

        $parser->emitted = [];
        $lexer->setInput('<textarea =a=\'"b"\' / c = d >', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(TagToken::class, $token);
        if ($token instanceof TagToken) {
            static::assertEquals(true, $token->isStartTag);
            static::assertEquals(false, $token->isSelfClosing);
            static::assertEquals('textarea', $token->name);
            static::assertCount(2, $token->attributes);
            static::assertEquals('"b"', $token->attributes['=a']);
            static::assertEquals('d', $token->attributes['c']);
        }

        $parser->emitted = [];
        $lexer->setInput('<div a=">', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(EofToken::class, $token);
    }

    public function testStepTokenize(): void
    {
        $parser = new TestLexerParser();
        $lexer = new Lexer($parser);

        $lexer->setInput('<', 0);
        $lexer->stepTokenize();
        static::assertCount(2, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(StringToken::class, $token);
        if ($token instanceof StringToken) {
            static::assertEquals('<', $token->value);
        }
        $token = $parser->emitted[1];
        static::assertInstanceOf(EofToken::class, $token);

        $parser->emitted = [];
        $lexer->setInput('', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(EofToken::class, $token);

        $parser->emitted = [];
        $lexer->setInput('&amp;', 0);
        $lexer->stepTokenize();
        static::assertCount(1, $parser->emitted);
        $token = $parser->emitted[0];
        static::assertInstanceOf(StringToken::class, $token);
        if ($token instanceof StringToken) {
            static::assertEquals('&', $token->value);
        }
    }
}
