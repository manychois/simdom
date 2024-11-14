<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Parsing\SelectorParser;
use Manychois\Cici\Selectors\AbstractSelector;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;
use Manychois\Simdom\AbstractNode;
use Manychois\Simdom\ChildNodeList;
use Manychois\Simdom\Css\MatchContext;
use Manychois\Simdom\Element;
use Manychois\Simdom\Text;

/**
 * Represents a node which can have child nodes in the DOM tree.
 */
abstract class AbstractParentNode extends AbstractNode
{
    public readonly ChildNodeList $childNodeList;

    /**
     * Constructs a new instance of this class.
     *
     * @param bool $isVoid True if this node is a void node, false otherwise.
     */
    public function __construct(bool $isVoid)
    {
        $this->childNodeList = $isVoid ? EmptyChildNodeList::instance() : new ChildNodeList();
    }

    /**
     * Parses a CSS selector string into a selector list.
     *
     * @param string $selector The CSS selector string.
     *
     * @return AbstractSelector The parsed selector list.
     */
    protected static function parseSelectorList(string $selector): AbstractSelector
    {
        $errors = new ParseExceptionCollection();
        $textStream = new TextStream($selector, $errors);
        $tokenizer = new Tokenizer();
        $parser = new SelectorParser();
        $tokenStream = $tokenizer->convertToTokenStream($textStream, false);
        $selector = $parser->parseSelectorList($tokenStream);
        if ($errors->count() > 0) {
            throw $errors->get(0);
        }

        return $selector;
    }

    /**
     * Sets the HTML content of this node.
     *
     * @param string $html The HTML content to set.
     */
    abstract public function setInnerHtml(string $html): void;

    /**
     * Validates if the given child nodes satisfy the structural constraints of this parent node.
     *
     * @param string|AbstractNode ...$futureChildren The nodes which are going to be the children of this node.
     */
    abstract protected function validatePreInsertion(string|AbstractNode ...$futureChildren): void;

    /**
     * Appends nodes to the end of the child node list of this node.
     * If a string is given, it will be converted to a text node.
     *
     * @param string|AbstractNode ...$childNodes The nodes to append.
     */
    public function append(string|AbstractNode ...$childNodes): void
    {
        $childNodeList = $this->childNodeList;
        $future = $childNodeList->toArray();
        $future = \array_merge($future, $childNodes);
        $this->validatePreInsertion(...$future);

        $childNodes = self::uniqueNodes(...$childNodes);
        self::detachAll(...$childNodes);
        $childNodeList->🚫append($this, ...$childNodes);
    }

    /**
     * Inserts nodes to the child node list of this node, before a reference node.
     * If a string is given, it will be converted to a text node.
     *
     * @param AbstractNode|null   $ref           The reference node. If null, the nodes will be inserted at the end.
     * @param string|AbstractNode ...$childNodes The nodes to insert.
     */
    public function insertBefore(?AbstractNode $ref, string|AbstractNode ...$childNodes): void
    {
        if ($ref === null) {
            $this->append(...$childNodes);

            return;
        }

        if ($ref->owner !== $this) {
            throw new \InvalidArgumentException('The reference node is not a child of this node.');
        }

        $viablePrevSibling = $ref;
        while (\in_array($viablePrevSibling, $childNodes, true)) {
            $viablePrevSibling = $viablePrevSibling->prevSibling();
        }
        $i = $viablePrevSibling === null ? 0 : $viablePrevSibling->index;

        $childNodeList = $this->childNodeList;
        $future = $childNodeList->toArray();
        // insertion may be inaccurate, but it does not affect the validation
        \array_splice($future, $i, 0, $childNodes);
        $this->validatePreInsertion(...$future);

        $childNodes = self::uniqueNodes(...$childNodes);
        self::detachAll(...$childNodes);
        $i = $viablePrevSibling === null ? 0 : $viablePrevSibling->index;
        $childNodeList->🚫insertAt($this, $i, ...$childNodes);
    }

    /**
     * Replaces a child node of this node with new nodes.
     *
     * @param AbstractNode        $oldChild         The child node to replace.
     * @param string|AbstractNode ...$newChildNodes The new nodes to replace the old child node.
     *                                              If a string is given, it will be converted to a text node.
     */
    public function replace(AbstractNode $oldChild, string|AbstractNode ...$newChildNodes): void
    {
        if ($oldChild->owner !== $this) {
            throw new \InvalidArgumentException('The node to be replaced is not a child of this node.');
        }

        $childNodeList = $this->childNodeList;
        $future = $childNodeList->toArray();
        $i = $oldChild->index;
        // replacement may be inaccurate, but it does not affect the validation
        \array_splice($future, $i, 1, $newChildNodes);
        $this->validatePreInsertion(...$future);

        $newChildNodes = self::uniqueNodes(...$newChildNodes);
        $viableNextSibling = $oldChild;
        while (\in_array($viableNextSibling, $newChildNodes, true)) {
            $viableNextSibling = $viableNextSibling->nextSibling();
        }
        self::detachAll(...$newChildNodes);
        $i = $viableNextSibling?->index;
        if ($i === null) {
            $childNodeList->🚫append($this, ...$childNodeList);
        } else {
            $childNodeList->🚫insertAt($this, $i, ...$newChildNodes);
        }
    }

    /**
     * Removes all child nodes of this node.
     */
    public function clear(): void
    {
        $this->childNodeList->🚫clear();
    }

    /**
     * Iterates all descendant nodes of this node.
     *
     * @return \Generator<int,AbstractNode> All descendant nodes of this node.
     */
    public function descendants(): \Generator
    {
        $i = 0;
        foreach ($this->childNodeList as $child) {
            yield $i => $child;

            $i++;
            if (!($child instanceof self)) {
                continue;
            }

            foreach ($child->descendants() as $inner) {
                yield $i => $inner;

                $i++;
            }
        }
    }

    /**
     * Iterates all descendant element nodes of this node.
     *
     * @return \Generator<int,Element> All descendant element nodes of this node.
     */
    public function descendantElements(): \Generator
    {
        foreach ($this->descendants() as $node) {
            if (!($node instanceof Element)) {
                continue;
            }

            yield $node;
        }
    }

    /**
     * Gets the first child node of this node.
     *
     * @return AbstractNode|null The first child node of this node, or null if there is none.
     */
    public function firstChild(): ?AbstractNode
    {
        return $this->childNodeList->get(0);
    }

    /**
     * Gets the first element child of this node.
     *
     * @return Element|null The first element child of this node, or null if there is none.
     */
    public function firstElementChild(): ?Element
    {
        foreach ($this->childNodeList as $child) {
            if ($child instanceof Element) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Gets the HTML content of this node.
     *
     * @return string The HTML content of this node.
     */
    public function innerHtml(): string
    {
        $html = '';
        foreach ($this->childNodeList as $node) {
            $html .= $node->toHtml();
        }

        return $html;
    }

    /**
     * Gets the last child node of this node.
     *
     * @return AbstractNode|null The last child node of this node, or null if there is none.
     */
    public function lastChild(): ?AbstractNode
    {
        return $this->childNodeList->get(-1);
    }

    /**
     * Gets the last element child of this node.
     *
     * @return Element|null The last element child of this node, or null if there is none.
     */
    public function lastElementChild(): ?Element
    {
        foreach ($this->childNodeList->reverse() as $child) {
            if ($child instanceof Element) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Removes any empty text nodes and combines adjacent text nodes within this node.
     */
    public function normalize(): void
    {
        $children = $this->childNodeList->toArray();
        $count = \count($children);
        $toRemove = [];
        $childElements = [];
        foreach ($children as $i => $child) {
            if ($child instanceof Text) {
                if ($child->data === '') {
                    $toRemove[] = $child;
                } else {
                    for ($j = $i + 1; $j < $count; ++$j) {
                        $next = $children[$j];
                        if (!($next instanceof Text)) {
                            break;
                        }

                        $child->data .= $next->data;
                        $next->data = '';
                    }
                }
            } elseif ($child instanceof Element) {
                $childElements[] = $child;
            }
        }
        if (\count($toRemove) > 0) {
            $this->childNodeList->🚫remove(...$toRemove);
        }
        foreach ($childElements as $element) {
            $element->normalize();
        }
    }

    /**
     * Prepends nodes to the beginning of the child node list of this node.
     * If a string is given, it will be converted to a text node.
     *
     * @param string|AbstractNode ...$childNodes The nodes to prepend.
     */
    public function prepend(string|AbstractNode ...$childNodes): void
    {
        $ref = $this->childNodeList->get(0);
        if ($ref === null) {
            $this->append(...$childNodes);
        } else {
            $this->insertBefore($ref, ...$childNodes);
        }
    }

    /**
     * Traverses the descendants of the node (in document order) and finds all elements that match the specified
     * class names.
     *
     * @param string $className One or more class names separated by whitespace.
     *
     * @return \Generator<int,Element> All matching elements in the descendants of the node.
     */
    public function queryAllClassName(string $className): \Generator
    {
        /** @var array<int,string> $tokens */
        $tokens = \preg_split('/\s+/', $className, -1, \PREG_SPLIT_NO_EMPTY);
        $tokens = \array_unique($tokens);
        if (\count($tokens) === 0) {
            return;
        }

        $regexes = \array_map(
            static fn (string $token): string => '/(^|\\s)' . \preg_quote($token, '/') . '(\\s|$)/',
            $tokens
        );
        foreach ($this->descendantElements() as $element) {
            $eleClassName = $element->getAttr('class');
            if ($eleClassName === null) {
                continue;
            }
            $allMatched = true;
            foreach ($regexes as $regex) {
                if (\preg_match($regex, $eleClassName) !== 1) {
                    $allMatched = false;

                    break;
                }
            }
            if (!$allMatched) {
                continue;
            }

            yield $element;
        }
    }

    /**
     * Traverses the descendants of the node (in document order) and finds all elements that match the specified
     * CSS selector.
     *
     * @param string $selector The CSS selector string.
     *
     * @return \Generator<int,Element> All matching elements in the descendants of the node.
     */
    public function queryAllCss(string $selector): \Generator
    {
        $selector = self::parseSelectorList($selector);
        $context = new MatchContext($this->root(), $this, []);
        foreach ($this->descendantElements() as $element) {
            if (!$selector->matches($context, $element)) {
                continue;
            }

            yield $element;
        }
    }

    /**
     * Traverses the descendants of the node (in document order) and finds all elements that match the specified
     * predicate.
     *
     * @param callable $predicate The predicate to match.
     *
     * @return \Generator<int,Element> All matching elements in the descendants of the node.
     *
     * @phpstan-param callable(Element):bool $predicate
     */
    public function queryAllElementFn(callable $predicate): \Generator
    {
        foreach ($this->descendantElements() as $element) {
            if (!$predicate($element)) {
                continue;
            }

            yield $element;
        }
    }

    /**
     * Traverses the descendants of the node (in document order) and finds all nodes that match the specified predicate.
     *
     * @param callable $predicate The predicate to match.
     *
     * @return \Generator<int,AbstractNode> All matching nodes in the descendants of the node.
     *
     * @phpstan-param callable(AbstractNode):bool $predicate
     */
    public function queryAllFn(callable $predicate): \Generator
    {
        foreach ($this->descendants() as $node) {
            if (!$predicate($node)) {
                continue;
            }

            yield $node;
        }
    }

    /**
     * Traverses the descendants of the node (in document order) and finds all elements with matching name attribute
     * value.
     *
     * @param string $name The value of the name attribute of the element.
     *
     * @return \Generator<int,Element> All matching elements in the descendants of the node.
     */
    public function queryAllName(string $name): \Generator
    {
        if ($name === '') {
            return;
        }

        foreach ($this->descendantElements() as $element) {
            if ($element->getAttr('name') !== $name) {
                continue;
            }

            yield $element;
        }
    }

    /**
     * Traverses the descendants of the node (in document order) and finds all element with the specified tag name.
     *
     * @param string $tagName The tag name of the element.
     *
     * @return \Generator<int,Element> All matching elements in the descendants of the node.
     */
    public function queryAllTagName(string $tagName): \Generator
    {
        if ($tagName === '') {
            throw new \InvalidArgumentException('The name cannot be empty.');
        }
        if (\str_contains($tagName, ' ')) {
            throw new \InvalidArgumentException('The name cannot contain whitespace.');
        }
        $tagName = \strtolower($tagName);
        foreach ($this->descendantElements() as $element) {
            if ($element->tagName !== $tagName) {
                continue;
            }

            yield $element;
        }
    }

    /**
     * Traverses the descendants of the node (in document order) until it finds an elements that match the specified
     * class names.
     *
     * @param string $className One or more class names separated by whitespace.
     *
     * @return Element|null The first matching element, or null if no element matches the class names.
     */
    public function queryClassName(string $className): ?Element
    {
        foreach ($this->queryAllClassName($className) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) until it finds an element that matches the specified
     * CSS selector.
     *
     * @param string $selector The CSS selector string.
     *
     * @return Element|null The first matching element, or null if no element matches the selector.
     */
    public function queryCss(string $selector): ?Element
    {
        foreach ($this->queryAllCss($selector) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) and finds an element that matches the specified
     * predicate.
     *
     * @param callable $predicate The predicate to match.
     *
     * @return Element|null The first matching element, or null if no element matches the predicate.
     *
     * @phpstan-param callable(Element):bool $predicate
     */
    public function queryElementFn(callable $predicate): ?Element
    {
        foreach ($this->queryAllElementFn($predicate) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) and finds a node that matches the specified predicate.
     *
     * @param callable $predicate The predicate to match.
     *
     * @return AbstractNode|null The first matching node, or null if no node matches the predicate.
     *
     * @phpstan-param callable(AbstractNode):bool $predicate
     */
    public function queryFn(callable $predicate): ?AbstractNode
    {
        foreach ($this->queryAllFn($predicate) as $node) {
            return $node;
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) until it finds an element with the specified ID.
     *
     * @param string $id The ID of the element.
     *
     * @return Element|null The first matching element, or null if no element matches the ID.
     */
    public function queryId(string $id): ?Element
    {
        foreach ($this->descendantElements() as $element) {
            if ($element->getAttr('id') === $id) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) until it finds an element with matching name attribute
     * value.
     *
     * @param string $name The value of the name attribute of the element.
     *
     * @return Element|null The first matching element, or null if no element matches the name attribute value.
     */
    public function queryName(string $name): ?Element
    {
        foreach ($this->queryAllName($name) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) until it finds an element with the specified tag name.
     *
     * @param string $tagName The name of the element.
     *
     * @return Element|null The first matching element, or null if no element matches the tag name.
     */
    public function queryTagName(string $tagName): ?Element
    {
        foreach ($this->queryAllTagName($tagName) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * Removes the given child node from the child node list of this node.
     *
     * @param AbstractNode $child The child node to remove.
     *
     * @return bool True if the child node is removed, false if it is not a child of this node.
     */
    public function remove(AbstractNode $child): bool
    {
        if ($child->owner !== $this) {
            return false;
        }

        $this->childNodeList->🚫remove($child);

        return true;
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    #region Internal methods

    #endregion Internal methods
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps


    #region extends AbstractNode

    /**
     * @inheritDoc
     */
    public function allTextData(array $excludes = ['head', 'script', 'style', 'template']): string
    {
        if ($this instanceof Element && \in_array($this->tagName, $excludes, true)) {
            return '';
        }

        $result = '';
        foreach ($this->childNodeList as $child) {
            if ($child instanceof Text) {
                $result .= $child->data;
            } elseif ($child instanceof Element) {
                if (!\in_array($child->tagName, $excludes, true)) {
                    $result .= $child->allTextData($excludes);
                }
            }
        }

        return $result;
    }

    #endregion extends AbstractNode


    /**
     * Clones the child nodes of this node and appends them to the target node.
     *
     * @param self $target The target node to append the cloned child nodes.
     */
    protected function cloneChildNodeList(self $target): void
    {
        foreach ($this->childNodeList as $child) {
            $clonedChild = $child->clone(true);
            $target->childNodeList->🚫append($target, $clonedChild);
        }
    }

    /**
     * Checks if the child node list of this node equals to the one of the given node.
     *
     * @param self $node The node to compare.
     *
     * @return bool True if the child node list of this node equals to the one of the given node, false otherwise.
     */
    protected function isEqualChildNodeList(self $node): bool
    {
        if ($this->childNodeList->count() !== $node->childNodeList->count()) {
            return false;
        }
        foreach ($this->childNodeList as $i => $child) {
            if (!$child->equals($node->childNodeList->get($i))) {
                return false;
            }
        }

        return true;
    }
}
