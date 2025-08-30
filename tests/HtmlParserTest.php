<?php

declare(strict_types=1);

namespace Manychois\SimdomTests;

use Generator;
use Manychois\Simdom\HtmlParser;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 *
 * @coversNothing
 */
class HtmlParserTest extends AbstractBaseTestCase
{
    #[DataProvider('provideParseDocumentData')]
    public function testParseDocument(string $inputPath, string $outputPath): void
    {
        $input = file_get_contents($inputPath);
        assert(false !== $input, 'Failed to read input file.');
        $parser = new HtmlParser();
        $doc = $parser->parseDocument($input);
        $expected = file_get_contents($outputPath);
        $output = $doc->__toString();
        $this->assertSame($expected, $output, 'Parsed document does not match expected output.');
    }

    public static function provideParseDocumentData(): Generator
    {
        $baseDir = __DIR__ . '/html-parser-test-cases';
        $files = glob($baseDir . '/input*.html');
        assert(is_array($files), 'Failed to read input files.');
        foreach ($files as $inputPath) {
            $outputPath = str_replace('input', 'output', $inputPath);
            yield [$inputPath, $outputPath];
        }
    }
}
