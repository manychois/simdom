<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use InvalidArgumentException;
use Manychois\Simdom\Document;
use Manychois\Simdom\DocumentFragment;
use Manychois\Simdom\Element;
use Manychois\Simdom\HTMLCollection;
use Manychois\Simdom\Node;
use Manychois\Simdom\NodeList;
use Manychois\Simdom\Text;
use Traversable;

abstract class BaseParentNode extends BaseNode implements ParentNode
{
    public readonly LiveNodeList $nodeList;
    private ?ChildElementList $eleList;

    public function __construct()
    {
        parent::__construct();
        $this->nodeList = new LiveNodeList($this);
        $this->eleList = null;
    }

    #region implements ParentNode properties

    public function childElementCount(): int
    {
        return $this->children()->length();
    }

    public function childNodes(): NodeList
    {
        return $this->nodeList;
    }

    public function children(): HTMLCollection
    {
        if ($this->eleList === null) {
            $this->eleList = new ChildElementList($this);
        }
        return $this->eleList;
    }

    public function firstChild(): ?Node
    {
        return $this->nodeList->item(0);
    }

    public function firstElementChild(): ?Element
    {
        return $this->children()->item(0);
    }

    public function lastChild(): ?Node
    {
        $count = $this->nodeList->length();
        return $this->nodeList->item($count - 1);
    }

    public function lastElementChild(): ?Element
    {
        $children = $this->children();
        return $children->item($children->length() - 1);
    }

    #endregion

    #region implements ParentNode methods

    public function append(Node|string ...$nodes): void
    {
        $nodes = $this->flattenNodes(...$nodes);
        $this->validatePreInsertion(null, $nodes);
        foreach ($nodes as $node) {
            $node->parent?->nodeList?->simRemove($node);
        }
        $this->nodeList->simAppend(...$nodes);
    }

    public function appendChild(Node $node): Node
    {
        $this->validatePreInsertion(null, $this->flattenNodes($node));
        if ($node instanceof DocumentFragment) {
            $children = $node->nodeList->clear();
            $this->nodeList->simAppend(...$children);
        } else {
            $node->parent?->nodeList?->simRemove($node);
            $this->nodeList->simAppend($node);
        }
        return $node;
    }

    public function contains(Node $node): bool
    {
        $ancestor = $node;
        while ($ancestor) {
            if ($ancestor === $this) {
                return true;
            }
            $ancestor = $ancestor->parent;
        }
        return false;
    }

    /**
     * @return Traversable<BaseNode>
     */
    public function dfs(): Traversable
    {
        $toVisit = [];
        foreach ($this->nodeList as $child) {
            $toVisit[] = $child;
        }
        while ($toVisit) {
            $current = array_shift($toVisit);
            yield $current;
            if ($current instanceof ElementNode && $current->nodeList->length()) {
                array_unshift($toVisit, ...$current->nodeList);
            }
        }
    }

    /**
     * @return Traversable<ElementNode>
     */
    public function dfsElements(): Traversable
    {
        $toVisit = [];
        foreach ($this->children() as $ele) {
            $toVisit[] = $ele;
        }
        while ($toVisit) {
            $current = array_shift($toVisit);
            yield $current;
            if ($current instanceof ElementNode && $current->childElementCount()) {
                array_unshift($toVisit, ...$current->children());
            }
        }
    }

    public function hasChildNodes(): bool
    {
        return $this->nodeList->length() > 0;
    }

    public function insertBefore(Node $node, ?Node $ref): Node
    {
        $this->validatePreInsertion($ref, $this->flattenNodes($node));
        $insertAt = $ref ? $this->nodeList->indexOf($ref) : -1;
        if ($node instanceof DocumentFragment) {
            $children = $node->nodeList->clear();
            if ($insertAt === -1) {
                $this->nodeList->simAppend(...$children);
            } else {
                $this->nodeList->simInsertAt($insertAt, ...$children);
            }
        } else {
            $node->parent?->nodeList?->simRemove($node);
            if ($insertAt === -1) {
                $this->nodeList->simAppend($node);
            } else {
                $this->nodeList->simInsertAt($insertAt, $node);
            }
        }
        return $node;
    }

    public function normalize(): void
    {
        $normalized = [];
        $elementsToCheck = [];

        if ($this instanceof Element) {
            $elementsToCheck[] = $this;
        } else {
            foreach ($this->nodeList as $child) {
                if ($child instanceof Element) {
                    $elementsToCheck[] = $child;
                }
            }
        }

        while ($elementsToCheck) {
            $element = array_shift($elementsToCheck);
            $prevText = null;
            foreach ($element->nodeList as $child) {
                if ($child instanceof Text) {
                    if ($child->data === '') {
                        continue;
                    }
                    if ($prevText) {
                        $prevText->data .= $child->data;
                    } else {
                        $normalized[] = $child;
                    }
                    $prevText = $child;
                } else {
                    $prevText = null;
                    $normalized[] = $child;
                    if ($child instanceof Element) {
                        $elementsToCheck[] = $child;
                    }
                }
            }
        }
    }

    public function prepend(Node|string ...$nodes): void
    {
        $nodes = $this->flattenNodes(...$nodes);
        $this->validatePreInsertion($this->nodeList->item(0), $nodes);
        foreach ($nodes as $node) {
            $node->parent?->nodeList?->simRemove($node);
        }
        $this->nodeList->simInsertAt(0, ...$nodes);
    }

    public function removeChild(Node $node): Node
    {
        $removed = $this->nodeList->simRemove($node);
        if (!$removed) {
            throw new InvalidArgumentException('The node is not a child of this node.');
        }
        return $node;
    }

    public function replaceChild(Node $new, Node $old): Node
    {
        $this->validatePreReplace($old, $this->flattenNodes($new));
        $replaceAt = $this->nodeList->indexOf($old);
        $this->nodeList->simRemoveAt($replaceAt);
        if ($new instanceof DocumentFragment) {
            $children = $new->nodeList->clear();
            $this->nodeList->simInsertAt($replaceAt, ...$children);
        } else {
            $this->nodeList->simInsertAt($replaceAt, $new);
        }
        return $old;
    }

    public function replaceChildren(Node|string ...$nodes): void
    {
        $nodes = $this->flattenNodes(...$nodes);
        $this->validatePreReplaceAll($nodes);
        foreach ($nodes as $node) {
            $node->parent?->nodeList?->simRemove($node);
        }
        $this->nodeList->clear();
        $this->nodeList->simAppend(...$nodes);
    }

    #endregion

    #region overrides BaseNode

    public function isEqualNode(Node $node): bool
    {
        if (get_class($node) !== static::class) {
            return false;
        }
        /** @var ParentNode $node */
        if ($this->nodeList->length() !== $node->nodeList->length()) {
            return false;
        }
        foreach ($this->nodeList as $i => $child) {
            if (!$child->isEqualNode($node->nodeList->item($i))) {
                return false;
            }
        }
        return true;
    }

    public function serialize(): string
    {
        $s = '';
        foreach ($this->nodeList as $child) {
            $s .= $child->serialize();
        }
        return $s;
    }

    public function textContent(): ?string
    {
        return null;
    }

    public function textContentSet(string $data): void
    {
    }

    #endregion

    /**
     * @param array<string|Node> $nodes
     * @return array<BaseNode>
     * @throws Exception
     */
    protected function flattenNodes(string|Node ...$nodes): array
    {
        $flattened = [];
        foreach ($nodes as $node) {
            if (is_string($node)) {
                $flattened[] = new Text($node);
            } elseif ($node instanceof DocumentFragment) {
                foreach ($node->childNodes() as $child) {
                    $index = array_search($node, $flattened, true);
                    if ($index !== false) {
                        array_splice($flattened, $index, 1);
                    }
                    $flattened[] = $child;
                }
            } else {
                $index = array_search($node, $flattened, true);
                if ($index !== false) {
                    array_splice($flattened, $index, 1);
                }
                $flattened[] = $node;
            }
        }
        return $flattened;
    }

    /**
     * @param null|Node $child
     * @param array<Node> $nodes
     * @link https://dom.spec.whatwg.org/#concept-node-ensure-pre-insertion-validity
     */
    protected function validatePreInsertion(?BaseNode $child, array $nodes): void
    {
        $getEx = fn (Node $node, string $msg) => new PreInsertionException($this, $node, $child, $msg);

        if ($child && !$this->contains($child)) {
            throw $getEx($child, 'The reference child is not found in the parent node.');
        }
        foreach ($nodes as $node) {
            if ($node instanceof ParentNode) {
                if ($node === $this) {
                    throw $getEx($node, 'A node cannot be its own child.');
                }
                if ($node->contains($this)) {
                    throw $getEx($node, 'A child node cannot contain its own ancestor.');
                }
                if ($node instanceof Document) {
                    throw $getEx($node, 'A document cannot be a child of another node.');
                }
            }
        }
    }

    /**
     * @param null|Node $old
     * @param array<Node> $newNodes
     * https://dom.spec.whatwg.org/#concept-node-replace
     */
    protected function validatePreReplace(BaseNode $old, array $newNodes): void
    {
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, $old, $msg);

        if ($old && !$this->contains($old)) {
            throw $getEx($old, 'The node to be replaced is not found in the parent node.');
        }
        foreach ($newNodes as $new) {
            if ($new instanceof ParentNode) {
                if ($new === $this) {
                    throw $getEx($new, 'A node cannot be its own child.');
                }
                if ($new->contains($this)) {
                    throw $getEx($new, 'A child node cannot contain its own ancestor.');
                }
                if ($new instanceof Document) {
                    throw $getEx($new, 'A document cannot be a child of another node.');
                }
            }
        }
    }

    /**
     * @param array<Node> $newNodes
     */
    protected function validatePreReplaceAll(array $newNodes): void
    {
        $getEx = fn (Node $node, string $msg) => new PreReplaceException($this, $node, null, $msg);

        foreach ($newNodes as $new) {
            if ($new instanceof ParentNode) {
                if ($new === $this) {
                    throw $getEx($new, 'A node cannot be its own child.');
                }
                if ($new->contains($this)) {
                    throw $getEx($new, 'A child node cannot contain its own ancestor.');
                }
                if ($new instanceof Document) {
                    throw $getEx($new, 'A document cannot be a child of another node.');
                }
            }
        }
    }
}
