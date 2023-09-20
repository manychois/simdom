<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Css;

use Manychois\Simdom\Dom;
use Manychois\Simdom\Internal\Css\ClassSelector;
use PHPUnit\Framework\TestCase;

class ClassSelectorTest extends TestCase
{
    public function testMatchWith(): void
    {
        $s = new ClassSelector('foo');

        $el = Dom::createElement('div');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('id', 'foo');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo');
        static::assertTrue($s->matchWith($el));

        $el->setAttribute('class', 'fool');
        static::assertFalse($s->matchWith($el));

        $el->setAttribute('class', 'foo bar');
        static::assertTrue($s->matchWith($el));
    }
}
