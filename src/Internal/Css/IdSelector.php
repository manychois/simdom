<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use InvalidArgumentException;
use Manychois\Simdom\ElementInterface;
use Manychois\Simdom\Internal\StringStream;

/**
 * Represents an ID selector.
 */
class IdSelector extends AbstractSubclassSelector
{
    /**
     * The ID to match.
     *
     * @var string
     */
    public readonly string $id;

    /**
     * Creates a new IdSelector instance.
     *
     * @param string $id The ID to match.
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Parses an ID selector.
     *
     * @param StringStream $str The string stream to parse.
     *
     * @return null|self The parsed ID selector, if available.
     */
    public static function parse(StringStream $str): ?self
    {
        $pattern = '/#(' . SelectorParser::CHAR_REGEX . '|[0-9-]' . ')*/';
        $matchResult = $str->regexMatch($pattern);
        assert($matchResult->success);
        $len = strlen($matchResult->value);
        assert($str->peek($len) === $matchResult->value);
        $id = SelectorParser::unescape(substr($matchResult->value, 1));
        if ($id === '') {
            throw new InvalidArgumentException('Invalid ID selector found');
        }
        $str->advance($len);

        return new IdSelector($id);
    }

    #region extends AbstractSubclassSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '#' . static::escIdent($this->id);
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        return $element->id() === $this->id;
    }

    #endregion
}
