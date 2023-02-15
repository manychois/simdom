<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

use DOMDocument;
use Manychois\Simdom\Document;
use Manychois\Simdom\DomNodeConverter;
use Manychois\Simdom\DOMParser;

class LegacyParser implements DOMParser
{
    public function parseFromString(string $source): Document
    {
        $prev = libxml_use_internal_errors(true);
        try {
            $rawDoc = new DOMDocument();
            $rawDoc->loadHTML($source);
            $converter = new DomNodeConverter();
            return $converter->convertToDocument($rawDoc);
        } finally {
            libxml_use_internal_errors($prev);
        }
    }
}
