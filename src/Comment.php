<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use InvalidArgumentException;
use Override;

final class Comment extends AbstractNode
{
    private string $textData;

    private function __construct(string $data)
    {
        $this->textData = $data;
    }

    public static function create(string $data): Comment
    {
        self::validateData($data);

        return new Comment($data);
    }

    public string $data {
        get => $this->textData;
        set(string $value) {
            self::validateData($value);
            $this->textData = $value;
        }
    }

    // region extends AbstractNode

    #[Override]
    public function clone(bool $deep = true): Comment
    {
        return new Comment($this->data);
    }

    #[Override]
    public function equals(AbstractNode $other): bool
    {
        if ($other === $this) {
            return true;
        }
        if (!$other instanceof Comment) {
            return false;
        }

        return $this->data === $other->data;
    }

    // endregion extends AbstractNode

    // region internal methods

    public static function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create(string $data): Comment
    {
        return new Comment($data);
    }

    // endregion internal methods

    private static function validateData(string $data): void
    {
        if (str_contains($data, '-->')) {
            throw new InvalidArgumentException('Comment data cannot contain "-->"');
        }
        self::validateNoControlCharacters($data, 'Comment data');
    }
}
