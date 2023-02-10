<?php

declare(strict_types=1);

namespace Manychois\Simdom\Parsing;

use Manychois\Simdom\Document;
use Manychois\Simdom\DomNs;
use Manychois\Simdom\DOMParser;
use Manychois\Simdom\Internal\CommentNode;
use Manychois\Simdom\Internal\DocNode;
use Manychois\Simdom\Internal\DoctypeNode;
use Manychois\Simdom\Internal\ElementNode;
use Manychois\Simdom\Internal\LiveNodeList;
use Manychois\Simdom\Internal\TextNode;
use Manychois\Simdom\Text;

class Parser implements DOMParser
{
    public OpenElementStack $stack;

    private string $mode;
    private ?Lexer $lexer;
    private ?DocNode $doc;
    private ?ElementNode $headPointer;

    public function __construct()
    {
        $this->stack = new OpenElementStack();
    }

    public function parse(string $html): DocNode
    {
        $this->mode = InsertionMode::INITIAL;
        $doc = new DocNode();
        $this->doc = $doc;
        $this->lexer = new Lexer($this);
        $this->headPointer = null;

        $this->stack->clear();
        $this->stack->context = null;
        $this->lexer->tokenize($html);

        $this->doc = null;
        $this->lexer = null;
        $this->headPointer = null;
        return $doc;
    }

    public function parsePartial(ElementNode $context, string $html): LiveNodeList
    {
        $doc = new DocNode();
        $root = new ElementNode('html');
        $doc->nodeList->simAppend($root);

        $this->doc = $doc;
        $this->lexer = new Lexer($this);
        $this->headPointer = null;

        $this->stack->clear();
        $this->stack->push($root);
        $this->stack->context = $context;

        $anythingElse = false;
        $contextNs = $context->namespaceURI();
        $contextName = $context->localName();
        if ($contextNs === DomNs::HTML) {
            if (in_array($contextName, ['title', 'textarea'], true)) {
                $this->lexer->setInput($html, 0);
                $root->nodeList->simAppend(new TextNode($this->lexer->consumeRcDataText($contextName)));
            } elseif (
                in_array($contextName, [
                    'style', 'xmp', 'iframe', 'noembed', 'noframes', 'script', 'noscript', 'template',
                ], true)
            ) {
                $this->lexer->setInput($html, 0);
                $root->nodeList->simAppend(new TextNode($this->lexer->consumeRawText($contextName)));
            } else {
                $anythingElse = true;
            }
        } else {
            $anythingElse = true;
        }

        if ($anythingElse) {
            // Simplified logic of resetting the insertion mode
            $this->mode = InsertionMode::IN_BODY;
            if ($contextNs === DomNs::HTML) {
                switch ($contextName) {
                    case 'head':
                        $this->mode = InsertionMode::IN_HEAD;
                        break;
                    case 'html':
                        $this->mode = InsertionMode::BEFORE_HEAD;
                        break;
                    default:
                        $this->mode = InsertionMode::IN_BODY;
                };
            }

            $this->lexer->tokenize($html);
        }

        $this->stack->context = null;
        $this->stack->clear();
        $this->doc = null;
        $this->lexer = null;
        $this->headPointer = null;
        return $root->nodeList;
    }

    public function treeConstruct(Token $token): void
    {
        $acn = $this->stack->current(true);
        $htmlContent = false;
        if ($acn === null) {
            $htmlContent = true;
        } elseif ($acn->namespaceURI() === DomNs::HTML) {
            $htmlContent = true;
        } elseif ($this->isMathMlTextIntegrationPoint($acn)) {
            if ($token instanceof TagToken && $token->isStartTag) {
                $htmlContent = $token->name !== 'mglyph' && $token->name !== 'malignmark';
            } elseif ($token instanceof StringToken) {
                $htmlContent = true;
            }
        } elseif ($acn->namespaceURI() === DomNs::MATHML && $acn->localName() === 'annotation-xml') {
            if ($token instanceof TagToken && $token->isStartTag) {
                $htmlContent = $token->name === 'svg';
            }
        } elseif ($token instanceof EofToken) {
            $htmlContent = true;
        }
        if (!$htmlContent && $this->isHtmlIntegrationPoint($acn)) {
            if ($token instanceof TagToken && $token->isStartTag) {
                $htmlContent = true;
            } elseif ($token instanceof StringToken) {
                $htmlContent = true;
            }
        }

        if ($htmlContent) {
            switch ($this->mode) {
                case InsertionMode::INITIAL:
                    $this->runInitialInsertionMode($token);
                    break;
                case InsertionMode::BEFORE_HTML:
                    $this->runBeforeHtmlInsertionMode($token);
                    break;
                case InsertionMode::BEFORE_HEAD:
                    $this->runBeforeHeadInsertionMode($token);
                    break;
                case InsertionMode::IN_HEAD:
                    $this->runInHeadInsertionMode($token);
                    break;
                case InsertionMode::AFTER_HEAD:
                    $this->runAfterHeadInsertionMode($token);
                    break;
                case InsertionMode::IN_BODY:
                    $this->runInBodyInsertionMode($token);
                    break;
                case InsertionMode::AFTER_BODY:
                    $this->runAfterBodyInsertionMode($token);
                    break;
                case InsertionMode::AFTER_AFTER_BODY:
                    $this->runAfterAfterBodyInsertionMode($token);
                    break;
            };
        } else {
            $this->runForeignContent($token);
        }
    }

    #region implements DOMParser

    public function parseFromString(string $source): Document
    {
        return $this->parse($source);
    }

    #endregion

    #region Insertion modes

    protected function runInitialInsertionMode(Token $token): void
    {
        $anythingElse = false;
        if ($token instanceof StringToken) {
            if (ctype_space($token->value)) {
                // Ignore
            } else {
                $token->value = ltrim($token->value);
                $anythingElse = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->doc->nodeList->simAppend(new CommentNode($token->value));
        } elseif ($token instanceof DoctypeToken) {
            $this->doc->nodeList->simAppend(
                new DoctypeNode($token->name ?? '', $token->publicId ?? '', $token->systemId ?? '')
            );
            $this->mode = InsertionMode::BEFORE_HTML;
        } else {
            $anythingElse = true;
        }
        if ($anythingElse) {
            $this->mode = InsertionMode::BEFORE_HTML;
            $this->runBeforeHtmlInsertionMode($token);
        }
    }

    protected function runBeforeHtmlInsertionMode(Token $token): void
    {
        $anythingElse = false;
        if ($token instanceof StringToken) {
            if (ctype_space($token->value)) {
                // Ignore
            } else {
                $token->value = ltrim($token->value);
                $anythingElse = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->doc->nodeList->simAppend(new CommentNode($token->value));
        } elseif ($token instanceof DoctypeToken) {
            // Ignore
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                if ($token->name === 'html') {
                    $e = $this->createElement($token);
                    $this->doc->nodeList->simAppend($e);
                    $this->stack->push($e);
                    $this->mode = InsertionMode::BEFORE_HEAD;
                } else {
                    $anythingElse = true;
                }
            } else {
                $anythingElse = $token->oneOf('html', 'head', 'body', 'br');
            }
        } else { // EofToken
            $anythingElse = true;
        }
        if ($anythingElse) {
            $e = new ElementNode('html', DomNs::HTML);
            $this->doc->nodeList->simAppend($e);
            $this->stack->push($e);
            $this->mode = InsertionMode::BEFORE_HEAD;
            $this->runBeforeHeadInsertionMode($token);
        }
    }

    protected function runBeforeHeadInsertionMode(Token $token): void
    {
        $anythingElse = false;
        if ($token instanceof StringToken) {
            if (ctype_space($token->value)) {
                // Ignore
            } else {
                $token->value = ltrim($token->value);
                $anythingElse = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Ignore
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                if ($token->name === 'html') {
                    $this->runInBodyInsertionMode($token);
                } elseif ($token->name === 'head') {
                    $this->headPointer = $this->insertForeignElement($token, DomNs::HTML);
                    $this->mode = InsertionMode::IN_HEAD;
                } else {
                    $anythingElse = true;
                }
            } else {
                $anythingElse = $token->oneOf('html', 'head', 'body', 'br');
            }
        } else {
            $anythingElse = true;
        }
        if ($anythingElse) {
            $this->headPointer = $this->insertForeignElement(new TagToken('head', true), DomNs::HTML);
            $this->mode = InsertionMode::IN_HEAD;
            $this->runInHeadInsertionMode($token);
        }
    }

    protected function runInHeadInsertionMode(Token $token): void
    {
        $anythingElse = false;
        if ($token instanceof StringToken) {
            preg_match('/^(\s*)(.*)$/s', $token->value, $matches);
            if ($matches[1] !== '') {
                $this->insertText($matches[1]);
            }
            if ($matches[2] !== '') {
                $token->value = $matches[2];
                $anythingElse = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Ignore
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                if ($token->name === 'html') {
                    $this->runInBodyInsertionMode($token);
                } elseif ($token->oneOf('base', 'basefont', 'bgsound', 'link', 'meta')) {
                    $this->insertForeignElement($token, DomNs::HTML, false);
                } elseif ($token->name === 'title') {
                    $this->insertForeignElement($token, DomNs::HTML);
                    $this->insertText($this->lexer->consumeRcDataText($token->name));
                    $this->stack->pop();
                } elseif ($token->oneOf('noframes', 'noscript', 'script', 'style', 'template')) {
                    $this->insertForeignElement($token, DomNs::HTML);
                    $this->insertText($this->lexer->consumeRawText($token->name));
                    $this->stack->pop();
                } elseif ($token->name === 'head') {
                    // Ignore
                } else {
                    $anythingElse = true;
                }
            } else {
                if ($token->name === 'head') {
                    $this->stack->pop();
                    $this->mode = InsertionMode::AFTER_HEAD;
                } else {
                    $anythingElse = $token->oneOf('body', 'html', 'br');
                }
            }
        } else {
            $anythingElse = true;
        }
        if ($anythingElse) {
            $this->stack->pop();
            $this->mode = InsertionMode::AFTER_HEAD;
            $this->runAfterHeadInsertionMode($token);
        }
    }

    protected function runAfterHeadInsertionMode(Token $token): void
    {
        $anythingElse = false;
        if ($token instanceof StringToken) {
            preg_match('/^(\s*)(.*)$/s', $token->value, $matches);
            if ($matches[1] !== '') {
                $this->insertText($matches[1]);
            }
            if ($matches[2] !== '') {
                $token->value = $matches[2];
                $anythingElse = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Ignore
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                if ($token->name === 'html') {
                    $this->runInBodyInsertionMode($token);
                } elseif ($token->name === 'body') {
                    $this->insertForeignElement($token, DomNs::HTML);
                    $this->mode = InsertionMode::IN_BODY;
                } elseif (
                    $token->oneOf(
                        'base',
                        'basefont',
                        'bgsound',
                        'link',
                        'meta',
                        'noframes',
                        'script',
                        'style',
                        'template',
                        'title'
                    )
                ) {
                    $this->stack->push($this->headPointer);
                    $this->runInHeadInsertionMode($token);
                    $this->stack->pop();
                } elseif ($token->name === 'head') {
                    // Ignore
                } else {
                    $anythingElse = true;
                }
            } else {
                $anythingElse = $token->oneOf('body', 'html', 'br');
            }
        } else {
            $anythingElse = true;
        }
        if ($anythingElse) {
            $this->insertForeignElement(new TagToken('body', true), DomNs::HTML);
            $this->mode = InsertionMode::IN_BODY;
            $this->runInBodyInsertionMode($token);
        }
    }

    protected function runInBodyInsertionMode(Token $token): void
    {
        if ($token instanceof StringToken) {
            $this->insertText($token->value);
        } elseif ($token instanceof CommentToken) {
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Ignore
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                if ($token->name === 'html') {
                    $htmlElement = $this->stack->item(0);
                    $this->fillMissingAttrs($token, $htmlElement);
                } elseif (
                    $token->oneOf(
                        'base',
                        'basefont',
                        'bgsound',
                        'link',
                        'meta',
                        'noframes',
                        'script',
                        'style',
                        'template',
                        'title'
                    )
                ) {
                    $this->runInHeadInsertionMode($token);
                } elseif ($token->name === 'body') {
                    $bodyElement = $this->stack->item(1);
                    $this->fillMissingAttrs($token, $bodyElement);
                } elseif ($token->oneOf('pre', 'listing')) {
                    $this->insertForeignElement($token, DomNs::HTML);
                    $this->lexer->trimNextLf = true;
                } elseif ($token->name === 'image') {
                    $token->name = 'img';
                    $this->insertForeignElement($token, DomNs::HTML);
                } elseif ($token->name === 'textarea') {
                    $this->insertForeignElement($token, DomNs::HTML);
                    $text = $this->lexer->consumeRcDataText($token->name);
                    if (($text[0] ?? '') === "\n") {
                        $text = substr($text, 1);
                    }
                    $this->insertText($text);
                    $this->stack->pop();
                } elseif ($token->oneOf('xmp', 'iframe', 'noembed', 'noscript')) {
                    $this->insertForeignElement($token, DomNs::HTML);
                    $this->insertText($this->lexer->consumeRawText($token->name));
                    $this->stack->pop();
                } elseif ($token->name === 'math') {
                    $this->insertForeignElement($token, DomNs::MATHML);
                } elseif ($token->name === 'svg') {
                    $this->insertForeignElement($token, DomNs::SVG);
                } elseif ($token->oneOf('head')) {
                    // Ignore
                } else {
                    $this->insertForeignElement($token, DomNs::HTML, !ElementNode::isVoid($token->name));
                }
            } else {
                if ($token->name === 'body') {
                    $this->mode = InsertionMode::AFTER_BODY;
                } elseif ($token->name === 'html') {
                    $this->mode = InsertionMode::AFTER_BODY;
                    $this->runAfterBodyInsertionMode($token);
                } else {
                    $this->stack->popMatching($token->name);
                }
            }
        } elseif ($token instanceof EofToken) {
            // Stop parsing
        }
    }

    protected function runAfterBodyInsertionMode(Token $token): void
    {
        $anythingElse = false;
        if ($token instanceof StringToken) {
            preg_match('/^(\s*)(.*)$/s', $token->value, $matches);
            if ($matches[1] !== '') {
                $this->insertText($matches[1]); // Process the token using "in body" insertion mode.
            }
            if ($matches[2] !== '') {
                $token->value = $matches[2];
                $anythingElse = true;
            }
        } elseif ($token instanceof CommentToken) {
            $htmlElement = $this->stack->item(0);
            $htmlElement->nodeList->simAppend(new CommentNode($token->value));
        } elseif ($token instanceof DoctypeToken) {
            // Ignore
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                if ($token->name === 'html') {
                    $this->runInBodyInsertionMode($token);
                } else {
                    $anythingElse = true;
                }
            } else {
                if ($token->name === 'html') {
                    $this->mode = InsertionMode::AFTER_AFTER_BODY;
                } else {
                    $anythingElse = true;
                }
            }
        } else { // EofToken
            // Stop parsing
        }
        if ($anythingElse) {
            $this->mode = InsertionMode::IN_BODY;
            $this->runInBodyInsertionMode($token);
        }
    }

    protected function runAfterAfterBodyInsertionMode(Token $token): void
    {
        $anythingElse = false;
        if ($token instanceof CommentToken) {
            $this->doc->nodeList->simAppend(new CommentNode($token->value));
        } elseif ($token instanceof DoctypeToken) {
            // Ignore
        } elseif ($token instanceof StringToken) {
            preg_match('/^(\s*)(.*)$/s', $token->value, $matches);
            if ($matches[1] !== '') {
                $this->insertText($matches[1]); // Process the token using "in body" insertion mode.
            }
            if ($matches[2] !== '') {
                $token->value = $matches[2];
                $anythingElse = true;
            }
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                if ($token->name === 'html') {
                    $this->runInBodyInsertionMode($token);
                } else {
                    $anythingElse = true;
                }
            } else {
                $anythingElse = true;
            }
        } elseif ($token instanceof EofToken) {
            // Stop parsing
        }
        if ($anythingElse) {
            $this->mode = InsertionMode::IN_BODY;
            $this->runInBodyInsertionMode($token);
        }
    }

    #endregion

    protected function adjustSvgTagName(string $tagName): string
    {
        switch ($tagName) {
            case 'altglyph':
                return 'altGlyph';
            case 'altglyphdef':
                return 'altGlyphDef';
            case 'altglyphitem':
                return 'altGlyphItem';
            case 'animatecolor':
                return 'animateColor';
            case 'animatemotion':
                return 'animateMotion';
            case 'animatetransform':
                return 'animateTransform';
            case 'clippath':
                return 'clipPath';
            case 'feblend':
                return 'feBlend';
            case 'fecolormatrix':
                return 'feColorMatrix';
            case 'fecomponenttransfer':
                return 'feComponentTransfer';
            case 'fecomposite':
                return 'feComposite';
            case 'feconvolvematrix':
                return 'feConvolveMatrix';
            case 'fediffuselighting':
                return 'feDiffuseLighting';
            case 'fedisplacementmap':
                return 'feDisplacementMap';
            case 'fedistantlight':
                return 'feDistantLight';
            case 'feflood':
                return 'feFlood';
            case 'fefunca':
                return 'feFuncA';
            case 'fefuncb':
                return 'feFuncB';
            case 'fefuncg':
                return 'feFuncG';
            case 'fefuncr':
                return 'feFuncR';
            case 'fegaussianblur':
                return 'feGaussianBlur';
            case 'feimage':
                return 'feImage';
            case 'femerge':
                return 'feMerge';
            case 'femergenode':
                return 'feMergeNode';
            case 'femorphology':
                return 'feMorphology';
            case 'feoffset':
                return 'feOffset';
            case 'fepointlight':
                return 'fePointLight';
            case 'fespecularlighting':
                return 'feSpecularLighting';
            case 'fespotlight':
                return 'feSpotLight';
            case 'fetile':
                return 'feTile';
            case 'feturbulence':
                return 'feTurbulence';
            case 'foreignobject':
                return 'foreignObject';
            case 'glyphref':
                return 'glyphRef';
            case 'lineargradient':
                return 'linearGradient';
            case 'radialgradient':
                return 'radialGradient';
            case 'textpath':
                return 'textPath';
            default:
                return $tagName;
        }
    }

    protected function createElement(TagToken $token, string $ns = DomNs::HTML): ElementNode
    {
        $adjustedName = $token->name;
        if ($ns === DomNs::SVG) {
            $adjustedName = $this->adjustSvgTagName($adjustedName);
        }
        $element = new ElementNode($adjustedName, $ns);
        if ($ns === DomNs::MATHML) {
            foreach ($token->attributes as $name => $value) {
                $this->adjustMathMlAttrs($name, $value, $element);
            }
        } elseif ($ns === DomNs::SVG) {
            foreach ($token->attributes as $name => $value) {
                $this->adjustSvgAttrs($name, $value, $element);
            }
        } else {
            $attrs = $element->attributes();
            foreach ($token->attributes as $name => $value) {
                $attrs->set($name, $value);
            }
        }
        return $element;
    }

    protected function fillMissingAttrs(TagToken $token, ElementNode $element): void
    {
        $attrs = $element->attributes();
        foreach ($token->attributes as $name => $value) {
            if ($attrs->getNamedItem($name) === null) {
                $attrs->set($name, $value);
            }
        }
    }

    protected function insertComment(CommentToken $token): void
    {
        $comment = new CommentNode($token->value);
        $parent = $this->stack->current();
        $parent->nodeList->simAppend($comment);
    }

    protected function insertForeignElement(TagToken $token, string $ns, bool $pushToStack = true): ElementNode
    {
        $element = $this->createElement($token, $ns);
        $parent = $this->stack->current();
        $parent->nodeList->simAppend($element);
        if ($pushToStack && !$token->isSelfClosing) {
            $this->stack->push($element);
        }
        return $element;
    }

    protected function insertText(string $value): void
    {
        $parent = $this->stack->current();
        $lastChild = $parent->lastChild();
        if ($lastChild instanceof Text) {
            $lastChild->appendData($value);
        } else {
            $parent->nodeList->simAppend(new TextNode($value));
        }
    }

    protected function isHtmlIntegrationPoint(ElementNode $element): bool
    {
        if ($element->namespaceURI() === DomNs::MATHML && $element->localName() === 'annotation-xml') {
            $encoding = $element->getAttribute('encoding');
            if ($encoding) {
                $encoding = strtolower($encoding);
            }
            return $encoding === 'text/html' || $encoding === 'application/xhtml+xml';
        } elseif ($element->namespaceURI() === DomNs::SVG) {
            return in_array($element->localName(), ['foreignObject', 'desc', 'title'], true);
        } else {
            return false;
        }
    }

    protected function isMathMlTextIntegrationPoint(ElementNode $element): bool
    {
        return $element->namespaceURI() === DomNs::MATHML
            && in_array($element->localName(), ['mi', 'mo', 'mn', 'ms', 'mtext'], true);
    }

    /**
     * @link https://html.spec.whatwg.org/multipage/parsing.html#parsing-main-inforeign
     */
    protected function runForeignContent(Token $token): void
    {
        if ($token instanceof StringToken) {
            $this->insertText($token->value);
        } elseif ($token instanceof CommentToken) {
            $this->insertComment($token);
        } elseif ($token instanceof DoctypeToken) {
            // Ignore the token.
        } elseif ($token instanceof TagToken) {
            if ($token->isStartTag) {
                $acn = $this->stack->current(true);
                $this->insertForeignElement($token, $acn->namespaceURI());
                if ($token->name === 'script') {
                    $this->insertText($this->lexer->consumeRawText('script'));
                    $this->stack->pop();
                }
            } else {
                $this->stack->popMatching($token->name);
            }
        }
    }

    #region Attribute adjustments

    /**
     * @link https://html.spec.whatwg.org/multipage/parsing.html#adjust-foreign-attributes
     */
    protected function adjustForeignAttrs(string $name, string $value, ElementNode $element): void
    {
        $attrs = $element->attributes();
        switch ($name) {
            case 'xlink:actuate':
                $attrs->setNs(DomNs::XLINK, 'xlink', 'actuate', $value);
                break;
            case 'xlink:arcrole':
                $attrs->setNs(DomNs::XLINK, 'xlink', 'arcrole', $value);
                break;
            case 'xlink:href':
                $attrs->setNs(DomNs::XLINK, 'xlink', 'href', $value);
                break;
            case 'xlink:role':
                $attrs->setNs(DomNs::XLINK, 'xlink', 'role', $value);
                break;
            case 'xlink:show':
                $attrs->setNs(DomNs::XLINK, 'xlink', 'show', $value);
                break;
            case 'xlink:title':
                $attrs->setNs(DomNs::XLINK, 'xlink', 'title', $value);
                break;
            case 'xlink:type':
                $attrs->setNs(DomNs::XLINK, 'xlink', 'type', $value);
                break;
            case 'xml:lang':
                $attrs->setNs(DomNs::XML, 'xml', 'lang', $value);
                break;
            case 'xml:space':
                $attrs->setNs(DomNs::XML, 'xml', 'space', $value);
                break;
            case 'xmlns':
                $attrs->setNs(DomNs::XMLNS, null, 'xmlns', $value);
                break;
            case 'xmlns:xlink':
                $attrs->setNs(DomNs::XMLNS, 'xmlns', 'xlink', $value);
                break;
            default:
                $attrs->set($name, $value);
        };
    }

    /**
     * Adjust MathML attributes for a token.
     * @link https://html.spec.whatwg.org/multipage/parsing.html#adjust-mathml-attributes
     */
    protected function adjustMathMlAttrs(string $name, string $value, ElementNode $element): void
    {
        if ($name === 'definitionurl') {
            $element->attributes()->set('definitionURL', $value);
        } else {
            $this->adjustForeignAttrs($name, $value, $element);
        }
    }

    /**
     * Adjust SVG attributes for a token.
     * @link https://html.spec.whatwg.org/multipage/parsing.html#adjust-svg-attributes
     */
    protected function adjustSvgAttrs(string $name, string $value, ElementNode $element): void
    {
        $adjustedName = false;
        switch ($name) {
            case 'attributename':
                $adjustedName = 'attributeName';
                break;
            case 'attributetype':
                $adjustedName = 'attributeType';
                break;
            case 'basefrequency':
                $adjustedName = 'baseFrequency';
                break;
            case 'baseprofile':
                $adjustedName = 'baseProfile';
                break;
            case 'calcmode':
                $adjustedName = 'calcMode';
                break;
            case 'clippathunits':
                $adjustedName = 'clipPathUnits';
                break;
            case 'diffuseconstant':
                $adjustedName = 'diffuseConstant';
                break;
            case 'edgemode':
                $adjustedName = 'edgeMode';
                break;
            case 'filterunits':
                $adjustedName = 'filterUnits';
                break;
            case 'glyphref':
                $adjustedName = 'glyphRef';
                break;
            case 'gradienttransform':
                $adjustedName = 'gradientTransform';
                break;
            case 'gradientunits':
                $adjustedName = 'gradientUnits';
                break;
            case 'kernelmatrix':
                $adjustedName = 'kernelMatrix';
                break;
            case 'kernelunitlength':
                $adjustedName = 'kernelUnitLength';
                break;
            case 'keypoints':
                $adjustedName = 'keyPoints';
                break;
            case 'keysplines':
                $adjustedName = 'keySplines';
                break;
            case 'keytimes':
                $adjustedName = 'keyTimes';
                break;
            case 'lengthadjust':
                $adjustedName = 'lengthAdjust';
                break;
            case 'limitingconeangle':
                $adjustedName = 'limitingConeAngle';
                break;
            case 'markerheight':
                $adjustedName = 'markerHeight';
                break;
            case 'markerunits':
                $adjustedName = 'markerUnits';
                break;
            case 'markerwidth':
                $adjustedName = 'markerWidth';
                break;
            case 'maskcontentunits':
                $adjustedName = 'maskContentUnits';
                break;
            case 'maskunits':
                $adjustedName = 'maskUnits';
                break;
            case 'numoctaves':
                $adjustedName = 'numOctaves';
                break;
            case 'pathlength':
                $adjustedName = 'pathLength';
                break;
            case 'patterncontentunits':
                $adjustedName = 'patternContentUnits';
                break;
            case 'patterntransform':
                $adjustedName = 'patternTransform';
                break;
            case 'patternunits':
                $adjustedName = 'patternUnits';
                break;
            case 'pointsatx':
                $adjustedName = 'pointsAtX';
                break;
            case 'pointsaty':
                $adjustedName = 'pointsAtY';
                break;
            case 'pointsatz':
                $adjustedName = 'pointsAtZ';
                break;
            case 'preservealpha':
                $adjustedName = 'preserveAlpha';
                break;
            case 'preserveaspectratio':
                $adjustedName = 'preserveAspectRatio';
                break;
            case 'primitiveunits':
                $adjustedName = 'primitiveUnits';
                break;
            case 'refx':
                $adjustedName = 'refX';
                break;
            case 'refy':
                $adjustedName = 'refY';
                break;
            case 'repeatcount':
                $adjustedName = 'repeatCount';
                break;
            case 'repeatdur':
                $adjustedName = 'repeatDur';
                break;
            case 'requiredextensions':
                $adjustedName = 'requiredExtensions';
                break;
            case 'requiredfeatures':
                $adjustedName = 'requiredFeatures';
                break;
            case 'specularconstant':
                $adjustedName = 'specularConstant';
                break;
            case 'specularexponent':
                $adjustedName = 'specularExponent';
                break;
            case 'spreadmethod':
                $adjustedName = 'spreadMethod';
                break;
            case 'startoffset':
                $adjustedName = 'startOffset';
                break;
            case 'stddeviation':
                $adjustedName = 'stdDeviation';
                break;
            case 'stitchtiles':
                $adjustedName = 'stitchTiles';
                break;
            case 'surfacescale':
                $adjustedName = 'surfaceScale';
                break;
            case 'systemlanguage':
                $adjustedName = 'systemLanguage';
                break;
            case 'tablevalues':
                $adjustedName = 'tableValues';
                break;
            case 'targetx':
                $adjustedName = 'targetX';
                break;
            case 'targety':
                $adjustedName = 'targetY';
                break;
            case 'textlength':
                $adjustedName = 'textLength';
                break;
            case 'viewbox':
                $adjustedName = 'viewBox';
                break;
            case 'viewtarget':
                $adjustedName = 'viewTarget';
                break;
            case 'xchannelselector':
                $adjustedName = 'xChannelSelector';
                break;
            case 'ychannelselector':
                $adjustedName = 'yChannelSelector';
                break;
            case 'zoomandpan':
                $adjustedName = 'zoomAndPan';
                break;
        }
        if ($adjustedName === false) {
            $this->adjustForeignAttrs($name, $value, $element);
        } else {
            $element->attributes()->set($adjustedName, $value);
        }
    }

    #endregion
}
