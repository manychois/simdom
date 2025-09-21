<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use Generator;
use Manychois\Simdom\Element;
use Manychois\Simdom\HtmlParser;
use Manychois\Simdom\PrettyPrinter;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 *
 * @coversNothing
 */
class PrettyPrinterTest extends AbstractBaseTestCase
{
    #[DataProvider('providePrintData')]
    public function testPrint(string $inputPath, string $outputPath): void
    {
        $input = file_get_contents($inputPath);
        assert(false !== $input, 'Failed to read input file.');
        $parser = new HtmlParser();
        $doc = $parser->parseDocument($input);
        $expected = file_get_contents($outputPath);
        $printer = new PrettyPrinter();
        $output = $printer->print($doc);
        self::assertSame($expected, $output);
    }

    public static function providePrintData(): Generator
    {
        $baseDir = __DIR__ . '/pretty-printer-test-cases';
        $files = glob($baseDir . '/input*.html');
        assert(is_array($files), 'Failed to read input files.');
        foreach ($files as $inputPath) {
            $outputPath = str_replace('input', 'output', $inputPath);
            yield [$inputPath, $outputPath];
        }
    }

    public function testPrintElement(): void
    {
        $div = Element::create('div');
        $p = Element::create('p');
        $div->append($p);
        $printer = new PrettyPrinter();
        $output = $printer->print($div);
        self::assertSame("<div>\n  <p></p>\n</div>", $output);
    }
}
