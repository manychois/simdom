<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal;

/**
 * Represents the kind of an element.
 */
enum ElementKind
{
    case Normal;
    case Void;
    case RawText;
    case EscapableRawText;

    /**
     * Identifies the kind of an element by its tag name.
     *
     * @param string $tagName The tag name of the element. Must be in lowercase.
     *
     * @return self The kind of the element.
     */
    public static function identify(string $tagName): self
    {
        return match ($tagName) {
            'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'
            => self::Void,
            'script', 'style', 'template' => self::RawText,
            'textarea', 'title' => self::EscapableRawText,
            default => self::Normal,
        };
    }
}
