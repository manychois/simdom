<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Generator;
use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Matching\NodeType;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\Combinator;
use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\AbstractParentNode;
use Manychois\Simdom\Comment;
use Manychois\Simdom\Doctype;
use Manychois\Simdom\Document;
use Manychois\Simdom\Element;
use Manychois\Simdom\Fragment;
use Manychois\Simdom\Text;
use Override;
use RuntimeException;

/**
 * @template-extends AbstractMatchContext<AbstractNode>
 */
final class MatchContext extends AbstractMatchContext
{
    // region extends AbstractMatchContext

    public function areOfSameElementType(object $node1, object $node2): bool
    {
        return $node1 instanceof Element
            && $node2 instanceof Element
            && $node1->name === $node2->name;
    }

    public function getAttributeValue(object $target, string|WqName $wqName): ?string
    {
        if (!$target instanceof Element) {
            return null;
        }

        $prefix = \is_string($wqName) ? null : $wqName->prefix;
        if (null !== $prefix) {
            throw new RuntimeException('Prefix is not supported in Simdom. Use local name only.');
        }

        $localName = \is_string($wqName) ? $wqName : $wqName->localName;

        return $target->getAttr($localName);
    }

    public function getNodeType(object $target): NodeType
    {
        return match ($target::class) {
            Comment::class => NodeType::Comment,
            Doctype::class => NodeType::DocumentType,
            Document::class => NodeType::Document,
            Element::class => NodeType::Element,
            Fragment::class => NodeType::DocumentFragment,
            Text::class => NodeType::Text,
            default => throw new RuntimeException('Unsupported node type: ' . $target::class),
        };
    }

    public function getParentNode(object $target): ?object
    {
        return $target->parent;
    }

    public function getRadioButtonGroup(object $target): array
    {
        assert($target instanceof Element, 'Target must be an Element');
        $name = $target->getAttr('name') ?? '';
        if ('' === $name) {
            return [$target];
        }

        $topmost = null;
        $owner = null;
        foreach ($this->loopAncestors($target, false) as $pNode) {
            if (null === $owner && $this->isHtmlElement($pNode, 'form')) {
                $owner = $pNode;
            }
            $topmost = $pNode;
        }

        if (null === $owner) {
            if (null === $topmost) {
                return [$target];
            }
            $owner = $topmost;
        }

        $group = [];
        foreach ($this->loopDescendants($owner, false) as $node) {
            if (!$this->isHtmlElement($node, 'input')) {
                continue;
            }

            assert($node instanceof Element);
            if ($node->getAttr('name') !== $name || 'radio' !== $node->getAttr('type')) {
                continue;
            }

            $group[] = $node;
        }

        return $group;
    }

    public function isActuallyDisabled(object $target): bool
    {
        if (!$this->isHtmlElement($target)) {
            return false;
        }
        assert($target instanceof Element);
        if (\in_array($target->name, ['button', 'input', 'select', 'textarea', 'fieldset'], true)) {
            if ($target->hasAttr('disabled')) {
                return true;
            }

            $fieldset = $this->firstAncestorHtmlElement(
                $target,
                false,
                function (Element $node) use ($target): bool {
                    if ('fieldset' !== $node->name) {
                        return false;
                    }
                    if (!$node->hasAttr('disabled')) {
                        return false;
                    }

                    foreach ($this->loopChildren($node) as $child) {
                        if ($this->isHtmlElement($child, 'legend')) {
                            return null === $this->firstDescendantHtmlElement(
                                $child,
                                false,
                                static fn ($e): bool => $e === $target
                            );
                        }
                    }

                    return true;
                }
            );
            if (null !== $fieldset) {
                return true;
            }
        }

        if ('optgroup' === $target->name && $target->hasAttr('disabled')) {
            return true;
        }

        if ('option' === $target->name) {
            if ($target->hasAttr('disabled')) {
                return true;
            }

            $parent = $target->parent;
            if (null !== $parent && $this->isHtmlElement($parent, 'optgroup')) {
                assert($parent instanceof Element);
                if ($parent->hasAttr('disabled')) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isHtmlElement(object $target, string ...$localNames): bool
    {
        if ($target instanceof Element) {
            return in_array($target->name, $localNames, true) || 0 === count($localNames);
        }

        return false;
    }

    public function isReadWritable(object $target): bool
    {
        $readWrite = false;
        if ($this->isHtmlElement($target, 'input')) {
            assert($target instanceof Element);
            $type = $target->getAttr('type');
            if (!\in_array($type, ['checkbox', 'color', 'file', 'hidden', 'radio', 'range'], true)) {
                if (null === $this->getAttributeValue($target, 'readonly')) {
                    $readWrite = !$this->isActuallyDisabled($target);
                }
            }
        } elseif ($this->isHtmlElement($target, 'textarea')) {
            if (null === $this->getAttributeValue($target, 'readonly')) {
                $readWrite = !$this->isActuallyDisabled($target);
            }
        } elseif ($this->isHtmlElement($target)) {
            assert($target instanceof Element);
            $isContentEditable = function (Element $ele): bool {
                $value = $this->getAttributeValue($ele, 'contenteditable');

                return null !== $value && 'false' !== $value;
            };
            $readWrite = null !== $this->firstAncestorHtmlElement($target, true, $isContentEditable);
        }

        return $readWrite;
    }

    /**
     * @return Generator<int,AbstractParentNode>
     */
    #[Override]
    public function loopAncestors(object $target, bool $includeSelf): Generator
    {
        if ($includeSelf && $target instanceof AbstractParentNode) {
            yield $target;
        }
        foreach ($target->ancestors() as $ancestor) {
            yield $ancestor;
        }
    }

    /**
     * @return Generator<int,Element>
     */
    #[Override]
    public function loopChildren(object $target): Generator
    {
        if ($target instanceof AbstractParentNode) {
            foreach ($target->childNodes as $child) {
                if ($child instanceof Element) {
                    yield $child;
                }
            }
        }
    }

    public function loopDescendants(object $target, bool $includeSelf): Generator
    {
        if ($includeSelf) {
            yield $target;
        }

        if ($target instanceof AbstractParentNode) {
            yield from $target->descendants();
        }
    }

    /**
     * @return Generator<int,Element>
     */
    #[Override]
    public function loopDescendantElements(object $target): Generator
    {
        if ($target instanceof AbstractParentNode) {
            foreach ($target->descendants() as $node) {
                if ($node instanceof Element) {
                    yield $node;
                }
            }
        }
    }

    /**
     * @return Generator<int,Element|Fragment>
     */
    #[Override]
    public function loopLeftCandidates(object $target, Combinator $combinator): Generator
    {
        \assert($target instanceof Element);
        if (Combinator::Descendant === $combinator) {
            foreach ($this->loopAncestors($target, false) as $node) {
                if (!($node instanceof Element) && !($node instanceof Fragment)) {
                    continue;
                }

                yield $node;
            }
        } elseif (Combinator::Child === $combinator) {
            $parent = $target->parent;
            if ($parent instanceof Element || $parent instanceof Fragment) {
                yield $parent;
            }
        } elseif (Combinator::NextSibling === $combinator) {
            $prev = $target->previousElementSibling;
            if (null !== $prev) {
                yield $prev;
            }
        } elseif (Combinator::SubsequentSibling === $combinator) {
            $prev = $target->previousElementSibling;
            while (null !== $prev) {
                yield $prev;

                $prev = $prev->previousElementSibling;
            }
        } else {
            throw new RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    /**
     * @return Generator<int,Element>
     */
    #[Override]
    public function loopRightCandidates(object $target, Combinator $combinator): Generator
    {
        if (Combinator::Descendant === $combinator) {
            foreach ($this->loopDescendants($target, false) as $child) {
                if (!($child instanceof Element)) {
                    continue;
                }

                yield $child;
            }
        } elseif (Combinator::Child === $combinator) {
            yield from $this->loopChildren($target);
        } elseif (Combinator::NextSibling === $combinator) {
            if ($target instanceof Element && null !== $target->nextElementSibling) {
                yield $target->nextElementSibling;
            }
        } elseif (Combinator::SubsequentSibling === $combinator) {
            if ($target instanceof Element) {
                $current = $target->nextElementSibling;
                while (null !== $current) {
                    yield $current;

                    $current = $current->nextElementSibling;
                }
            }
        } else {
            throw new RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    public function matchElementType(object $target, WqName $wqName): bool
    {
        if (!$target instanceof Element) {
            return false;
        }

        $prefix = $wqName->prefix;
        if (null !== $prefix) {
            throw new RuntimeException('Prefix is not supported in Simdom. Use local name only.');
        }

        return '*' === $wqName->localName || $target->name === $wqName->localName;
    }

    public function matchDefaultNamespace(object $target): bool
    {
        return true;
    }

    // endregion extends AbstractMatchContext

    /**
     * Gets the first ancestor HTML element that matches the specified predicate.
     *
     * @param AbstractNode $target      the node to start from
     * @param bool         $includeSelf whether to include the target node itself
     * @param callable     $predicate   the predicate to match
     *
     * @phpstan-param callable(Element):bool $predicate
     *
     * @return Element|null the first ancestor HTML element that matches the predicate, or `null` if not found
     */
    private function firstAncestorHtmlElement(AbstractNode $target, bool $includeSelf, callable $predicate): ?Element
    {
        foreach ($this->loopAncestors($target, $includeSelf) as $node) {
            if (!$this->isHtmlElement($node)) {
                continue;
            }

            assert($node instanceof Element);
            if ($predicate($node)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the first descendant HTML element that matches the specified predicate.
     *
     * @param AbstractNode $target      the element to start from
     * @param bool         $includeSelf whether to include the target node itself
     * @param callable     $predicate   the predicate to match
     *
     * @phpstan-param callable(Element):bool $predicate
     *
     * @return Element|null the first descendant HTML element that matches the predicate, or `null` if not found
     */
    private function firstDescendantHtmlElement(AbstractNode $target, bool $includeSelf, callable $predicate): ?Element
    {
        foreach ($this->loopDescendants($target, $includeSelf) as $node) {
            if (!$this->isHtmlElement($node)) {
                continue;
            }

            assert($node instanceof Element);
            if ($predicate($node)) {
                return $node;
            }
        }

        return null;
    }
}
