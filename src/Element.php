<?php

declare(strict_types=1);

namespace Manychois\Simdom;

use InvalidArgumentException;
use Override;
use WeakReference;

final class Element extends AbstractParentNode
{
    public readonly string $name;
    /**
     * @var array<string,string>
     */
    private array $attrs = [];
    /**
     * @var null|WeakReference<DomTokenList>
     */
    private ?WeakReference $classListRef = null;

    private function __construct(string $name)
    {
        parent::__construct();
        $this->name = $name;
    }

    public static function create(string $name): Element
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Element name cannot be empty');
        }
        self::validateNoControlCharacters($name, 'Element name');
        self::validateNoWhitespace($name, 'Element name');
        self::validateNoCharacters($name, '/>', 'Element name');
        if (preg_match('/^[A-Za-z]/', $name) !== 1) {
            throw new InvalidArgumentException('Element name must start with a letter');
        }
        $name = strtolower($name);
        return new Element($name);
    }

    public DomTokenList $classList {
        get {
            $dtl = $this->classListRef?->get();
            if (!($dtl instanceof DomTokenList)) {
                $dtl = new DomTokenList($this, 'class');
                $this->classListRef = WeakReference::create($dtl);

                return $dtl;
            }

            return $dtl;
        }
    }

    public string $className {
        get => $this->getAttr('class') ?? '';
        set(string $value) {
            $this->setAttr('class', $value);
        }
    }

    public string $id {
        get => $this->getAttr('id') ?? '';
        set(string $value) {
            $this->setAttr('id', $value);
        }
    }

    public string $innerHtml {
        get => self::htmlSerialiser()->serialiseNodeList($this->childNodes);
        set(string $value) {
            $p = new HtmlParser();
            $p->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙ChangeInnerHtml($this, $value);
        }
    }

    public string $openTagHtml {
        get => self::htmlSerialiser()->serialiseOpenTag($this);
    }

    public string $outerHtml {
        get => self::htmlSerialiser()->serialiseElement($this);
        set(string $value) {
            if (null !== $this->parent) {
                $context = $this->parent instanceof Element ? $this->parent->name : '';
                $p = new HtmlParser();
                $frag = $p->parseFragment($value, $context);
                $nodes = $frag->childNodes->asArray();
                $frag->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Clear();
                $this->parent->childNodes->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙replaceAt($this->index, ...$nodes);
            }
        }
    }

    /**
     * @return array<string,string>
     */
    public function attrs(): array
    {
        return $this->attrs;
    }

    public function getAttr(string $name): ?string
    {
        return $this->attrs[strtolower($name)] ?? null;
    }

    public function hasAttr(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->attrs);
    }

    public function removeAttr(string $name): void
    {
        unset($this->attrs[strtolower($name)]);
    }

    public function setAttr(string $name, string $value): void
    {
        if ('' === $name) {
            throw new InvalidArgumentException('Attribute name cannot be empty');
        }
        self::validateNoControlCharacters($name, 'Attribute name');
        self::validateNoWhitespace($name, 'Attribute name');
        self::validateNoCharacters($name, '/>', 'Attribute name');
        if (false !== strpos($name, '=', 1)) {
            throw new InvalidArgumentException('Attribute name can only have "=" at the start');
        }
        self::validateNoControlCharacters($value, 'Attribute value');

        $name = strtolower($name);
        $this->attrs[$name] = $value;

        if ('class' === $name) {
            $dtl = $this->classListRef?->get();
            if ($dtl instanceof DomTokenList) {
                $dtl->𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SyncFromOwner();
            }
        }
    }

    // region extends AbstractParentNode

    #[Override]
    public function clone(bool $deep = true): AbstractNode
    {
        $element = new Element($this->name);
        $element->attrs = $this->attrs;
        if ($deep) {
            $element->copyChildNodesFrom($this);
        }

        return $element;
    }

    #[Override]
    public function equals(AbstractNode $other): bool
    {
        if ($other === $this) {
            return true;
        }

        if (!$other instanceof Element) {
            return false;
        }

        if ($this->name !== $other->name) {
            return false;
        }

        if (count($this->attrs) !== count($other->attrs)) {
            return false;
        }
        foreach ($this->attrs as $name => $value) {
            if (!array_key_exists($name, $other->attrs) || $other->attrs[$name] !== $value) {
                return false;
            }
        }

        return parent::equals($other);
    }

    // endregion extends AbstractParentNode

    // region internal methods and properties

    public static function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙Create(string $name): Element
    {
        return new Element($name);
    }

    public bool $𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRawtext {
        get => in_array($this->name, [
            'iframe',
            'noembed',
            'noframes',
            'noscript',
            'plaintext',
            'script',
            'style',
            'template',
            'xmp',
        ], true);
    }

    public bool $𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsRcdata {
        get => in_array($this->name, ['title', 'textarea'], true);
    }

    public bool $𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙IsVoid {
        get => in_array($this->name, [
            'area',
            'base',
            'br',
            'col',
            'embed',
            'hr',
            'img',
            'input',
            'link',
            'meta',
            'source',
            'track',
            'wbr',
        ], true);
    }

    public function 𝑖𝑛𝑡𝑒𝑟𝑛𝑎𝑙SetAttr(string $name, string $value): void
    {
        $this->attrs[$name] = $value;
    }

    // endregion internal methods
}
