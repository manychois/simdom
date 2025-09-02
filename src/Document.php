<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use DomainException;
use Override;

/**
 * Represents a document in the DOM.
 */
final class Document extends AbstractParentNode
{
    /**
     * Creates a new Document instance.
     *
     * @return Document the created Document instance
     */
    public static function create(): Document
    {
        return new Document();
    }

    public ?Element $body {
        get => $this->documentElement?->childNodes->findElement(static fn (Element $e): bool => 'body' === $e->name || 'frameset' === $e->name);
    }

    public ?Doctype $doctype {
        get {
            $found = $this->childNodes->find(static fn (AbstractNode $node): bool => $node instanceof Doctype);
            assert(null === $found || $found instanceof Doctype);

            return $found;
        }
    }

    public ?Element $documentElement {
        get {
            $found = $this->childNodes->find(static fn (AbstractNode $node): bool => $node instanceof Element);
            assert(null === $found || $found instanceof Element);

            return $found;
        }
    }

    public ?Element $head {
        get => $this->documentElement?->childNodes->findElement(static fn (Element $e): bool => 'head' === $e->name);
    }

    /**
     * Validates the document structure.
     */
    public function validate(): void
    {
        $hasDoctype = false;
        $hasRootElement = false;
        foreach ($this->childNodes as $node) {
            if ($node instanceof Doctype) {
                if ($hasDoctype) {
                    throw new DomainException('Document can only have one doctype');
                }
                if ($hasRootElement) {
                    throw new DomainException('Doctype must be before the root element');
                }
                $hasDoctype = true;
            }
            if ($node instanceof Text) {
                throw new DomainException('Document cannot contain text nodes');
            }
            if ($node instanceof Element) {
                if ($hasRootElement) {
                    throw new DomainException('Document can only have one root element');
                }
                $hasRootElement = true;
            }
        }
    }

    // region extends AbstractParentNode

    #[Override]
    public function clone(bool $deep = true): Document
    {
        $doc = new Document();
        if ($deep) {
            $doc->copyChildNodesFrom($this);
        }

        return $doc;
    }

    // endregion extends AbstractParentNode
}
