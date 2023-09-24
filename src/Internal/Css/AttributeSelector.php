<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Css;

use Manychois\Simdom\ElementInterface;

/**
 * Represents an attribute selector.
 */
class AttributeSelector extends AbstractSelector
{
    /**
     * The attribute name to match.
     *
     * @var string
     */
    public readonly string $name;

    /**
     * The attribute matcher, i.e. how to compare the attribute value.
     *
     * @var AttrMatcher
     */
    public readonly AttrMatcher $matcher;

    /**
     * The attribute value to match.
     */
    public readonly string $value;

    /**
     * Whether the attribute value comparison is case sensitive.
     *
     * @var bool
     */
    public readonly bool $caseSensitive;

    /**
     * Creates a new AttributeSelector instance.
     *
     * @param string      $name          The attribute name to match.
     * @param AttrMatcher $matcher       The attribute matcher, i.e. how to compare the attribute value.
     * @param string      $value         The attribute value to match.
     * @param bool        $caseSensitive Whether the attribute value comparison is case sensitive.
     */
    public function __construct(string $name, AttrMatcher $matcher, string $value, bool $caseSensitive)
    {
        $this->name = mb_strtolower($name);
        $this->matcher = $matcher;
        $this->value = $value;
        $this->caseSensitive = $caseSensitive;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $str = '[' . $this->name;
        if ($this->matcher === AttrMatcher::Exists) {
            return $str . ']';
        }

        $str .= $this->matcher->value;

        $value = preg_replace_callback('/[\\\\"\\n]/', static fn ($matches) => match ($matches[0]) {
            "\n" => '\\a ',
            '"' => '\\"',
            default => '\\\\',
        }, $this->value);
        $str .= '"' . $value . '"';

        if (!$this->caseSensitive) {
            $str .= ' i';
        }
        $str .= ']';

        return $str;
    }

    /**
     * @inheritDoc
     */
    public function matchWith(ElementInterface $element): bool
    {
        if (!$element->hasAttribute($this->name)) {
            return false;
        }

        $attrValue = $element->getAttribute($this->name) ?? '';
        $pregQuote = preg_quote($this->value, '/');
        $pattern = match ($this->matcher) {
            AttrMatcher::Exists => '',
            AttrMatcher::Equals => '/^' . $pregQuote . '$/u',
            AttrMatcher::Includes => '/(^|\\s)' . $pregQuote . '($|\\s)/u',
            AttrMatcher::DashMatch => '/^' . $pregQuote . '(-|$)/u',
            AttrMatcher::PrefixMatch => '/^' . $pregQuote . '/u',
            AttrMatcher::SuffixMatch => '/' . $pregQuote . '$/u',
            AttrMatcher::SubstringMatch => '/' . $pregQuote . '/u',
        };
        if ($pattern !== '' && !$this->caseSensitive) {
            $pattern .= 'i';
        }

        return $pattern === '' || preg_match($pattern, $attrValue) === 1;
    }

    #endregion
}
