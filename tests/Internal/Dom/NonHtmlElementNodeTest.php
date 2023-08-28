<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal\Dom;

use Manychois\Simdom\Dom;
use Manychois\Simdom\NamespaceUri;
use PHPUnit\Framework\TestCase;

class NonHtmlElementNodeTest extends TestCase
{
    public function testSetAttribute(): void
    {
        $svg = Dom::createElement('svg', NamespaceUri::Svg);
        $svg->setAttribute('VIEWBOX', '0 0 100 100');
        static::assertSame('0 0 100 100', $svg->getAttribute('viewBox'));
        $svg->setAttribute('viewbox', '0 0 200 200');
        static::assertSame('0 0 200 200', $svg->getAttribute('VIEWBOX'));

        foreach ($svg->attributes() as $name => $value) {
            static::assertSame('viewBox', $name);
            static::assertSame('0 0 200 200', $value);
        }
    }
}
