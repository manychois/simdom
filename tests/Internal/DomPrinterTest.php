<?php

declare(strict_types=1);

namespace Manychois\SimdomTests\Internal;

use Manychois\Simdom\Dom;
use PHPUnit\Framework\TestCase;

class DomPrinterTest extends TestCase
{
    public function testPrintWithDefaultOption(): void
    {
        $html = <<<'HTML'
<!doctype html>
<html>
<head>
    <title>Test</title>
</head>
<!-- Comment between head & body -->
<body>
    <form method="GET">
        <label for="name">Name</label>
        <input type="text" id='name' name="name" value="John Doe" required>
        <input type="submit" value="Submit" />
    </form>
</body>
</html>
HTML;
        $expected = <<<'HTML'
<!DOCTYPE html><html><head>
    <title>Test</title>
</head>
<!-- Comment between head & body -->
<body>
    <form method="GET">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="John Doe" required>
        <input type="submit" value="Submit">
    </form>

</body></html>
HTML;
        $parser = Dom::createParser();
        $doc = $parser->parseFromString($html);
        $output = Dom::print($doc);
        static::assertSame($expected, $output);
    }
}
