<?php

declare(strict_types=1);

namespace Manychois\Simdom;

/**
 * Represents the type of a node.
 */
enum NodeType: int
{
    case Document = \XML_DOCUMENT_NODE;
    case Element = \XML_ELEMENT_NODE;
    case Text = \XML_TEXT_NODE;
    case Comment = \XML_COMMENT_NODE;
}
