<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Parsing\SelectorParser;
use Manychois\Cici\Selectors\AbstractSelector;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;

/**
 * Parses a list of CSS selectors.
 */
final class SelectorListParser
{
    private Tokenizer $tokenizer;
    private SelectorParser $parser;

    /**
     * Creates a new instance of SelectorListParser.
     */
    public function __construct()
    {
        $this->tokenizer = new Tokenizer();
        $this->parser = new SelectorParser();
    }

    /**
     * Parses a CSS selector string and returns the corresponding AbstractSelector object.
     *
     * @param string $selector the CSS selector string to parse
     *
     * @return AbstractSelector the parsed AbstractSelector object
     */
    public function parse(string $selector): AbstractSelector
    {
        $errors = new ParseExceptionCollection();
        $textStream = new TextStream($selector, $errors);
        $tokenStream = $this->tokenizer->convertToTokenStream($textStream, false);
        $selectorList = $this->parser->parseSelectorList($tokenStream);
        if ($errors->count() > 0) {
            throw $errors->get(0);
        }

        return $selectorList;
    }
}
