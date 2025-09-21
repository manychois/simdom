<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Internal\SelectorListParser;
use Manychois\SimdomTests\AbstractBaseTestCase;

/**
 * @internal
 *
 * @covers \Manychois\Simdom\Internal\SelectorListParser
 */
class SelectorListParserTest extends AbstractBaseTestCase
{
    public function testParse(): void
    {
        $parser = new SelectorListParser();
        $result = $parser->parse('div, span.class, #id[attr="value"]');
        self::assertEquals('div,span.class,#id[attr="value"]', $result->__toString());
    }
}
