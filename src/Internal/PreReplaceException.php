<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Node;

class PreReplaceException extends InvalidArgumentException
{
    public ParentNode $parent;
    public ?Node $old;
    public Node $node;

    public function __construct(ParentNode $parent, Node $node, ?Node $old, string $message)
    {
        parent::__construct($message);
        $this->parent = $parent;
        $this->old = $old;
        $this->node = $node;
    }
}
