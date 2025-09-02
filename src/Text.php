<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use Override;

/**
 * Represents a text node in the DOM.
 */
final class Text extends AbstractNode
{
    private string $textData;

    private function __construct(string $data)
    {
        $this->textData = $data;
    }

    /**
     * Creates a new Text node with the given string data.
     *
     * @param string $data the text content for the node
     *
     * @return Text the created Text node
     */
    public static function create(string $data): Text
    {
        self::validateNoControlCharacters($data, 'Text data');

        return new Text($data);
    }

    public string $data {
        get => $this->textData;
        set(string $value) {
            self::validateNoControlCharacters($value, 'Text data');
            $this->textData = $value;
        }
    }

    // region extends AbstractNode

    #[Override]
    public function clone(bool $deep = true): Text
    {
        return new Text($this->data);
    }

    #[Override]
    public function equals(AbstractNode $other): bool
    {
        if ($other === $this) {
            return true;
        }
        if (!$other instanceof Text) {
            return false;
        }

        return $this->data === $other->data;
    }

    // endregion extends AbstractNode

    // region internal methods

    /**
     * Creates a new Text node without validation.
     *
     * @param string $data the text content for the node
     *
     * @return Text the created Text node
     *
     * @internal
     */
    public static function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create(string $data): Text
    {
        return new Text($data);
    }

    // endregion internal methods
}
