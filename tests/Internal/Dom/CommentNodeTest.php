<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use InvalidArgumentException;
use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class CommentNodeTest extends TestCase
{
    public function testSetData(): void
    {
        $comment = Dom::createComment();
        static::assertSame('', $comment->data());
        $comment->setData('new data');
        static::assertSame('new data', $comment->data());
    }

    public function testSetDataException(): void
    {
        $comment = Dom::createComment();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"-->" will terminate the parsing of a comment.');
        $comment->setData('new --> data');
    }
}
