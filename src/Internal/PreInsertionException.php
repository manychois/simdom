<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Node;

class PreInsertionException extends InvalidArgumentException
{
    public ParentNode $parent;
    public Node $node;
    public ?Node $refChild;

    public function __construct(ParentNode $parent, Node $node, ?Node $refChild, string $message)
    {
        parent::__construct($message);
        $this->parent = $parent;
        $this->node = $node;
        $this->refChild = $refChild;
    }
}
