<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

use Manychois\Simdom\Element;

class OpenElementStack
{
    public ?Element $context;
    /**
     * @var array<int, Element>
     */
    private array $stack;

    public function __construct()
    {
        $this->context = null;
        $this->stack = [];
    }

    public function clear(): void
    {
        $this->stack = [];
    }

    public function current(bool $adjusted = false): ?Element
    {
        $count = count($this->stack);
        if ($adjusted && $this->context && $count === 1) {
            return $this->context;
        }
        return $count === 0 ? null : $this->stack[$count - 1];
    }

    public function item(int $index): ?Element
    {
        return $this->stack[$index] ?? null;
    }

    public function pop(): ?Element
    {
        return array_pop($this->stack);
    }

    public function popMatching(string $endTagName): void
    {
        $popped = [];
        for ($i = 0; $i < 3; ++$i) { // we test 3 levels at most
            $element = $this->pop();
            if ($element === null) {
                break;
            }
            $popped[] = $element;
            if ($element->localName() === $endTagName) {
                return;
            }
        }
        // we didn't find a matching element, so we put back the popped elements
        foreach (array_reverse($popped) as $element) {
            $this->push($element);
        }
    }

    public function push(Element $element): void
    {
        $this->stack[] = $element;
    }
}
