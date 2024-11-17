<?php

declare(strict_types=1);

namespace Manychois\Simdom\Css;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Matching\NodeType as CiciNodeType;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\Combinator;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\AbstractParentNode;
use Manychois\Simdom\NodeType;

/**
 * Represents a match context for nodes in this library.
 *
 * @template-extends AbstractMatchContext<\Manychois\Simdom\AbstractNode>
 */
class MatchContext extends AbstractMatchContext
{
    #region extends AbstractMatchContext

    /**
     * @inheritDoc
     */
    public function areOfSameElementType(object $node1, object $node2): bool
    {
        return $node1 instanceof Element &&
            $node2 instanceof Element &&
            $node1->tagName === $node2->tagName;
    }

    /**
     * @inheritDoc
     */
    public function getAttributeValue(object $target, string|WqName $wqName): ?string
    {
        if (!($target instanceof Element)) {
            return null;
        }

        if (\is_string($wqName)) {
            return $target->getAttr($wqName);
        }

        return $target->getAttr($wqName->localName);
    }

    /**
     * @inheritDoc
     */
    public function getNodeType(object $target): CiciNodeType
    {
        return match ($target->nodeType()) {
            NodeType::Comment => CiciNodeType::Comment,
            NodeType::Document => CiciNodeType::Document,
            NodeType::Element => CiciNodeType::Element,
            NodeType::Text => CiciNodeType::Text,
        };
    }

    /**
     * @inheritDoc
     */
    public function getParentNode(object $target): ?object
    {
        return $target->parent();
    }

    /**
     * @inheritDoc
     */
    public function getRadioButtonGroup(object $target): array
    {
        \assert($target instanceof Element);
        $name = $target->getAttr('name');
        if ($name === null) {
            return [$target];
        }

        $topmost = null;
        $owner = null;
        foreach ($target->ancestors() as $pNode) {
            if ($owner === null && $pNode instanceof Element && $pNode->tagName === 'form') {
                $owner = $pNode;
            }
            $topmost = $pNode;
        }

        if ($owner === null) {
            if ($topmost === null) {
                return [$target];
            }
            $owner = $topmost;
        }

        $group = [];
        foreach ($owner->descendantElements() as $element) {
            if ($element->tagName !== 'input') {
                continue;
            }

            if ($element->getAttr('name') !== $name || $element->getAttr('type') !== 'radio') {
                continue;
            }

            $group[] = $element;
        }

        return $group;
    }

    /**
     * @inheritDoc
     */
    public function isActuallyDisabled(object $target): bool
    {
        if (!($target instanceof Element)) {
            return false;
        }

        if (\in_array($target->tagName, ['button', 'input', 'select', 'textarea', 'fieldset'], true)) {
            if ($target->hasAttr('disabled')) {
                return true;
            }

            $fieldset = $target->closestFn(
                static function (Element $node) use ($target): bool {
                    if ($node === $target) {
                        return false;
                    }
                    if ($node->tagName !== 'fieldset') {
                        return false;
                    }
                    if (!$node->hasAttr('disabled')) {
                        return false;
                    }

                    foreach ($node->childNodeList->elements() as $child) {
                        if ($child->tagName === 'legend') {
                            return $child->queryElementFn(static fn ($e): bool => $e === $target) === null;
                        }
                    }

                    return true;
                }
            );
            if ($fieldset !== null) {
                return true;
            }
        }

        if ($target->tagName === 'optgroup' && $target->hasAttr('disabled')) {
            return true;
        }

        if ($target->tagName === 'option') {
            if ($target->hasAttr('disabled')) {
                return true;
            }

            $parent = $target->parent();
            if ($parent instanceof Element && $parent->tagName === 'optgroup') {
                if ($parent->hasAttr('disabled')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isHtmlElement(object $target, string ...$localNames): bool
    {
        if (!($target instanceof Element)) {
            return false;
        }

        return \in_array($target->tagName, $localNames, true);
    }

    /**
     * @inheritDoc
     */
    public function isReadWritable(object $target): bool
    {
        $readWrite = false;
        if ($target instanceof Element) {
            if ($target->tagName === 'input') {
                $type = $target->getAttr('type');
                if (!\in_array($type, ['checkbox', 'color', 'file', 'hidden', 'radio', 'range'], true)) {
                    if (!$target->hasAttr('readonly')) {
                        $readWrite = !$this->isActuallyDisabled($target);
                    }
                }
            } elseif ($target->tagName === 'textarea') {
                if (!$target->hasAttr('readonly')) {
                    $readWrite = !$this->isActuallyDisabled($target);
                }
            } else {
                $readWrite = $target->closestFn(static function (Element $ele): bool {
                    $value = $ele->getAttr('contenteditable');

                    return $value !== null && $value !== 'false';
                }) !== null;
            }
        }

        return $readWrite;
    }

    /**
     * @inheritDoc
     */
    public function loopAncestors(object $target, bool $includeSelf): \Generator
    {
        if ($includeSelf) {
            if ($target instanceof AbstractParentNode) {
                yield $target;
            }
        }

        yield from $target->ancestors();
    }

    /**
     * @inheritDoc
     */
    public function loopChildren(object $target): \Generator
    {
        if (!($target instanceof AbstractParentNode)) {
            return;
        }

        yield from $target->childNodeList->elements();
    }

    /**
     * @inheritDoc
     */
    public function loopDescendants(object $target, bool $includeSelf): \Generator
    {
        if ($includeSelf) {
            yield $target;
        }
        if (!($target instanceof AbstractParentNode)) {
            return;
        }

        yield from $target->descendants();
    }

    /**
     * @inheritDoc
     */
    public function loopDescendantElements(object $target): \Generator
    {
        \assert($target instanceof AbstractParentNode);

        yield from $target->descendantElements();
    }

    /**
     * @inheritDoc
     */
    public function loopLeftCandidates(object $target, Combinator $combinator): \Generator
    {
        \assert($target instanceof Element);
        if ($combinator === Combinator::Descendant) {
            foreach ($target->ancestors() as $ancestor) {
                if (!($ancestor instanceof Element)) {
                    continue;
                }

                yield $ancestor;
            }
        } elseif ($combinator === Combinator::Child) {
            $parent = $target->parent();
            if ($parent instanceof Element) {
                yield $parent;
            }
        } elseif ($combinator === Combinator::NextSibling) {
            $prev = $target->prevElementSibling();
            if ($prev !== null) {
                yield $prev;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            $prev = $target->prevElementSibling();
            while ($prev !== null) {
                yield $prev;

                $prev = $prev->prevElementSibling();
            }
        } else {
            throw new \RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    /**
     * @inheritDoc
     */
    public function loopRightCandidates(object $target, Combinator $combinator): \Generator
    {
        if (!($target instanceof AbstractParentNode)) {
            return;
        }
        if ($combinator === Combinator::Descendant) {
            yield from $target->descendantElements();
        } elseif ($combinator === Combinator::Child) {
            yield from $target->childNodeList->elements();
        } elseif ($combinator === Combinator::NextSibling) {
            $next = $target->nextElementSibling();
            if ($next !== null) {
                yield $next;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            $next = $target->nextElementSibling();
            while ($next !== null) {
                yield $next;

                $next = $next->nextElementSibling();
            }
        } else {
            throw new \RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    /**
     * @inheritDoc
     */
    public function matchElementType(object $target, WqName $wqName): bool
    {
        if (!($target instanceof Element)) {
            return false;
        }

        return $wqName->localName === '*' || $target->tagName === $wqName->localName;
    }

    /**
     * @inheritDoc
     */
    public function matchDefaultNamespace(object $target): bool
    {
        return true;
    }

    #endregion extends AbstractMatchContext
}
