<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use InvalidArgumentException;
use Override;

final class Doctype extends AbstractNode
{
    public readonly string $name;
    public readonly string $publicId;
    public readonly string $systemId;

    private function __construct(string $name, string $publicId, string $systemId)
    {
        $this->name = $name;
        $this->publicId = $publicId;
        $this->systemId = $systemId;
    }

    public static function create(string $name, string $publicId = '', string $systemId = ''): Doctype
    {
        self::validateNoControlCharacters($name, 'Doctype name');
        self::validateNoWhitespace($name, 'Doctype name');
        self::validateNoCharacters($name, '>', 'Doctype name');
        if ($name === '' && ($publicId !== '' || $systemId !== '')) {
            throw new InvalidArgumentException('Doctype name cannot be empty if public or system ID is provided.');
        }
        self::validateNoControlCharacters($publicId, 'Public ID');
        self::validateNoCharacters($publicId, '>', 'Public ID');
        if (str_contains($publicId, '\'') && str_contains($publicId, '"')) {
            throw new InvalidArgumentException('Public ID cannot contain both single and double quotes.');
        }
        self::validateNoControlCharacters($systemId, 'System ID');
        self::validateNoCharacters($systemId, '>', 'System ID');
        if (str_contains($systemId, '\'') && str_contains($systemId, '"')) {
            throw new InvalidArgumentException('System ID cannot contain both single and double quotes.');
        }

        return new self($name, $publicId, $systemId);
    }

    // region extends AbstractNode

    #[Override]
    public function clone(bool $deep = true): AbstractNode
    {
        return new Doctype($this->name, $this->publicId, $this->systemId);
    }

    #[Override]
    public function equals(AbstractNode $other): bool
    {
        if ($other === $this) {
            return true;
        }
        if (!$other instanceof Doctype) {
            return false;
        }

        return $this->name === $other->name
            && $this->publicId === $other->publicId
            && $this->systemId === $other->systemId;
    }

    // endregion extends AbstractNode

    // region internal methods

    public static function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create(string $name, string $publicId, string $systemId): Doctype
    {
        return new self($name, $publicId, $systemId);
    }

    // endregion internal methods
}
