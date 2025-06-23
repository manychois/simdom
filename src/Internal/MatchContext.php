<?php

namespace Manychois\Simdom\Internal;

use Generator;
use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Matching\NodeType;
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
        return $node1 instanceof Element &&
            $node2 instanceof Element &&
            $node1->name === $node2->name;
    }

    public function getAttributeValue(object $target, string|WqName $wqName): ?string
    {
        if (!$target instanceof Element) {
            return null;
        }

        $prefix = \is_string($wqName) ? null : $wqName->prefix;
        if ($prefix !== null) {
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
        if ($name === '') {
            return [$target];
        }

        $topmost = null;
        $owner = null;
        foreach ($this->loopAncestors($target, false) as $pNode) {
            if ($owner === null && $this->isHtmlElement($pNode, 'form')) {
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
        foreach ($this->loopDescendants($owner, false) as $node) {
            if (!$this->isHtmlElement($node, 'input')) {
                continue;
            }

            assert($node instanceof Element);
            if ($node->getAttr('name') !== $name || $node->getAttr('type') !== 'radio') {
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
                    if ($node->name !== 'fieldset') {
                        return false;
                    }
                    if (!$node->hasAttr('disabled')) {
                        return false;
                    }

                    foreach ($this->loopChildren($node) as $child) {
                        if ($this->isHtmlElement($child, 'legend')) {
                            return $this->firstDescendantHtmlElement(
                                $child,
                                false,
                                static fn($e): bool => $e === $target
                            ) === null;
                        }
                    }

                    return true;
                }
            );
            if ($fieldset !== null) {
                return true;
            }
        }

        if ($target->name === 'optgroup' && $target->hasAttr('disabled')) {
            return true;
        }

        if ($target->name === 'option') {
            if ($target->hasAttr('disabled')) {
                return true;
            }

            $parent = $target->parent;
            if ($parent !== null && $this->isHtmlElement($parent, 'optgroup')) {
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
            return in_array($target->name, $localNames, true) || count($localNames) === 0;
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
                if ($this->getAttributeValue($target, 'readonly') === null) {
                    $readWrite = !$this->isActuallyDisabled($target);
                }
            }
        } elseif ($this->isHtmlElement($target, 'textarea')) {
            if ($this->getAttributeValue($target, 'readonly') === null) {
                $readWrite = !$this->isActuallyDisabled($target);
            }
        } elseif ($this->isHtmlElement($target)) {
            assert($target instanceof Element);
            $isContentEditable = function (Element $ele): bool {
                $value = $this->getAttributeValue($ele, 'contenteditable');

                return $value !== null && $value !== 'false';
            };
            $readWrite = $this->firstAncestorHtmlElement($target, true, $isContentEditable) !== null;
        }

        return $readWrite;
    }

    /**
     * @inheritDoc
     *
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
     * @inheritDoc
     *
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
     * @inheritDoc
     *
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
     * @inheritDoc
     *
     * @return Generator<int,Element|Fragment>
     */
    #[Override]
    public function loopLeftCandidates(object $target, Combinator $combinator): Generator
    {
        \assert($target instanceof Element);
        if ($combinator === Combinator::Descendant) {
            foreach ($this->loopAncestors($target, false) as $node) {
                if (!($node instanceof Element) && !($node instanceof Fragment)) {
                    continue;
                }

                yield $node;
            }
        } elseif ($combinator === Combinator::Child) {
            $parent = $target->parent;
            if ($parent instanceof Element || $parent instanceof Fragment) {
                yield $parent;
            }
        } elseif ($combinator === Combinator::NextSibling) {
            $prev = $target->previousElementSibling;
            if ($prev !== null) {
                yield $prev;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            $prev = $target->previousElementSibling;
            while ($prev !== null) {
                yield $prev;

                $prev = $prev->previousElementSibling;
            }
        } else {
            throw new \RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    /**
     * @inheritDoc
     *
     * @return Generator<int,Element>
     */
    #[Override]
    public function loopRightCandidates(object $target, Combinator $combinator): Generator
    {
        if ($combinator === Combinator::Descendant) {
            foreach ($this->loopDescendants($target, false) as $child) {
                if (!($child instanceof Element)) {
                    continue;
                }

                yield $child;
            }
        } elseif ($combinator === Combinator::Child) {
            yield from $this->loopChildren($target);
        } elseif ($combinator === Combinator::NextSibling) {
            if ($target instanceof Element && $target->nextElementSibling !== null) {
                yield $target->nextElementSibling;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            if ($target instanceof Element) {
                $current = $target->nextElementSibling;
                while ($current !== null) {
                    yield $current;

                    $current = $current->nextElementSibling;
                }
            }
        } else {
            throw new \RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    public function matchElementType(object $target, WqName $wqName): bool
    {
        if (!$target instanceof Element) {
            return false;
        }

        $prefix = $wqName->prefix;
        if ($prefix !== null) {
            throw new RuntimeException('Prefix is not supported in Simdom. Use local name only.');
        }

        return $wqName->localName === '*' || $target->name === $wqName->localName;
    }

    public function matchDefaultNamespace(object $target): bool
    {
        return true;
    }

    // endregion extends AbstractMatchContext

    /**
     * Gets the first ancestor HTML element that matches the specified predicate.
     *
     * @param AbstractNode $target      The node to start from.
     * @param bool     $includeSelf Whether to include the target node itself.
     * @param callable $predicate   The predicate to match.
     *
     * @return Element|null The first ancestor HTML element that matches the predicate, or `null` if not found.
     *
     * @phpstan-param callable(Element):bool $predicate
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
     * @param AbstractNode $target      The element to start from.
     * @param bool         $includeSelf Whether to include the target node itself.
     * @param callable     $predicate   The predicate to match.
     *
     * @return Element|null The first descendant HTML element that matches the predicate, or `null` if not found.
     *
     * @phpstan-param callable(Element):bool $predicate
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
