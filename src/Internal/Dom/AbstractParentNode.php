<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\ParentNodeInterface;

/**
 * Internal implementation of ParentNodeInterface.
 */
abstract class AbstractParentNode extends AbstractNode implements ParentNodeInterface
{
    /**
     * @var array<int, AbstractNode>
     */
    protected array $cNodes = [];
}
