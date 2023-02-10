<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Manychois\Simdom\Internal\ParentNode;

/**
 * Represents a node in a DOM tree.
 */
interface Node
{
    #region Node type constants

    public const ELEMENT_NODE = 1;
    public const TEXT_NODE = 3;
    public const COMMENT_NODE = 8;
    public const DOCUMENT_NODE = 9;
    public const DOCUMENT_TYPE_NODE = 10;
    public const DOCUMENT_FRAGMENT_NODE = 11;

    #endregion

    #region Node properties

    /**
     * Returns the next node in the tree, or null if there isn't such node.
     */
    public function nextSibling(): ?Node;

    /**
     * Returns the node type.
     */
    public function nodeType(): int;

    /**
     * Returns the `Document` that this node belongs to.
     * If the node is a `Document`, then it returns `null`.
     */
    public function ownerDocument(): ?Document;

    /**
     * Returns the parent node which is also an `Element`, or null otherwise.
     */
    public function parentElement(): ?Element;

    /**
     * Returns the parent of this node, or null if the node is the top of the tree or it doesn't participate in a tree.
     */
    public function parentNode(): ?ParentNode;

    /**
     * Returns the previous node in the tree, or null if there isn't such node.
     */
    public function previousSibling(): ?Node;

    /**
     * Returns the textual content of the node and all its descendants.
     */
    public function textContent(): ?string;

    /**
     * Sets the textual content of the node and all its descendants.
     */
    public function textContentSet(string $data): void;

    #endregion

    #region Node methods

    /**
     * Clones the node, and optionally, all of its contents.
     * @param bool $deep If `true`, the node and its descendants are cloned.
     *                   If `false`, the node is cloned without its descendants.
     */
    public function cloneNode(bool $deep = false): self;

    /**
     * Returns the topmost ancestor of this node in a tree.
     * It returns the node itself if it is already at the top of the tree.
     */
    public function getRootNode(): Node;

    /**
     * Indicates whether or not two nodes are of the same type and all their defining data points match.
     */
    public function isEqualNode(Node $node): bool;

    #endregion
}
