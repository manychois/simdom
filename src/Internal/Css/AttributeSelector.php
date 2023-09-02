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
        $s = '[' . $this->name;
        if ($this->matcher === AttrMatcher::Exists) {
            return $s . ']';
        }

        $s .= $this->matcher->value;

        $value = preg_replace_callback('/[\\\\"\\n]/', static fn ($matches) => match ($matches[0]) {
            "\n" => '\\a ',
            '"' => '\\"',
            default => '\\\\',
        }, $this->value);
        $s .= '"' . $value . '"';

        if ($this->caseSensitive) {
            $s .= ' i';
        }
        $s .= ']';

        return $s;
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
        switch ($this->matcher) {
            case AttrMatcher::Equals:
                return $this->caseSensitive
                    ? $attrValue === $this->value
                    : mb_strtolower($attrValue, 'UTF-8') === mb_strtolower($this->value, 'UTF-8');
            case AttrMatcher::Includes:
                $pattern = '/(^|\\s)' . preg_quote($this->value, '/') . '($|\\s)/u';
                if (!$this->caseSensitive) {
                    $pattern .= 'i';
                }

                return preg_match($pattern, $attrValue) === 1;
            case AttrMatcher::DashMatch:
                $pattern = '/^' . preg_quote($this->value, '/') . '(-|$)/u';
                if (!$this->caseSensitive) {
                    $pattern .= 'i';
                }

                return preg_match($pattern, $attrValue) === 1;
            case AttrMatcher::PrefixMatch:
                $pattern = '/^' . preg_quote($this->value, '/') . '/u';
                if (!$this->caseSensitive) {
                    $pattern .= 'i';
                }

                return preg_match($pattern, $attrValue) === 1;
            case AttrMatcher::SuffixMatch:
                $pattern = '/' . preg_quote($this->value, '/') . '$/u';
                if (!$this->caseSensitive) {
                    $pattern .= 'i';
                }

                return preg_match($pattern, $attrValue) === 1;
            case AttrMatcher::SubstringMatch:
                $pattern = '/' . preg_quote($this->value, '/') . '/u';
                if (!$this->caseSensitive) {
                    $pattern .= 'i';
                }

                return preg_match($pattern, $attrValue) === 1;
            default:
                return true;
        }
    }

    #endregion
}
