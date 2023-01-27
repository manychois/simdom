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
use Manychois\Simdom\Internal\TextNode;
use Manychois\Simdom\Text;

class Parser implements DOMParser
{
    public readonly OpenElementStack $stack;

    private InsertionMode $mode;
    private ?Lexer $lexer;
    private ?DocNode $doc;
    private ?ElementNode $headPointer;

    public function __construct()
    {
        $this->stack = new OpenElementStack();
    }

    public function parse(string $html): DocNode
    {
        $this->mode = InsertionMode::Initial;
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

    public function parsePartial(ElementNode $context, string $html): void
    {
        $this->mode = InsertionMode::Initial;
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
        if ($context->namespaceURI() === DomNs::Html) {
            $tagName = $context->localName();
            if (in_array($tagName, ['title', 'textarea'], true)) {
                $this->lexer->setInput($html, 0);
                $context->nodeList->simAppend(new TextNode($this->lexer->consumeRcDataText($tagName)));
            } elseif (
                in_array($tagName, [
                'style', 'xmp', 'iframe', 'noembed', 'noframes', 'script', 'noscript', 'template',
                ], true)
            ) {
                $this->lexer->setInput($html, 0);
                $context->nodeList->simAppend(new TextNode($this->lexer->consumeRawText($tagName)));
            } else {
                $anythingElse = true;
            }
        } else {
            $anythingElse = true;
        }

        if ($anythingElse) {
            $this->lexer->tokenize($html);
        }

        $this->stack->context = null;
        $this->stack->clear();
        $this->doc = null;
        $this->lexer = null;
        $this->headPointer = null;
    }

    public function treeConstruct(Token $token): void
    {
        $acn = $this->stack->current(true);
        $htmlContent = false;
        if ($acn === null) {
            $htmlContent = true;
        } elseif ($acn->namespaceURI() === DomNs::Html) {
            $htmlContent = true;
        } elseif ($this->isMathMlTextIntegrationPoint($acn)) {
            if ($token instanceof TagToken && $token->isStartTag) {
                $htmlContent = $token->name !== 'mglyph' && $token->name !== 'malignmark';
            } elseif ($token instanceof StringToken) {
                $htmlContent = true;
            }
        } elseif ($acn->namespaceURI() === DomNs::MathMl && $acn->localName() === 'annotation-xml') {
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
            match ($this->mode) {
                InsertionMode::Initial => $this->runInitialInsertionMode($token),
                InsertionMode::BeforeHtml => $this->runBeforeHtmlInsertionMode($token),
                InsertionMode::BeforeHead => $this->runBeforeHeadInsertionMode($token),
                InsertionMode::InHead => $this->runInHeadInsertionMode($token),
                InsertionMode::AfterHead => $this->runAfterHeadInsertionMode($token),
                InsertionMode::InBody => $this->runInBodyInsertionMode($token),
                InsertionMode::AfterBody => $this->runAfterBodyInsertionMode($token),
                InsertionMode::AfterAfterBody => $this->runAfterAfterBodyInsertionMode($token),
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
            $this->mode = InsertionMode::BeforeHtml;
        } else {
            $anythingElse = true;
        }
        if ($anythingElse) {
            $this->mode = InsertionMode::BeforeHtml;
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
                    $this->mode = InsertionMode::BeforeHead;
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
            $e = new ElementNode('html', DomNs::Html);
            $this->doc->nodeList->simAppend($e);
            $this->stack->push($e);
            $this->mode = InsertionMode::BeforeHead;
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
                    $this->headPointer = $this->insertForeignElement($token, DomNs::Html);
                    $this->mode = InsertionMode::InHead;
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
            $this->headPointer = $this->insertForeignElement(new TagToken('head', true), DomNs::Html);
            $this->mode = InsertionMode::InHead;
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
                    $this->insertForeignElement($token, DomNs::Html, false);
                } elseif ($token->name === 'title') {
                    $this->insertForeignElement($token, DomNs::Html);
                    $this->insertText($this->lexer->consumeRcDataText($token->name));
                    $this->stack->pop();
                } elseif ($token->oneOf('noframes', 'noscript', 'script', 'style', 'template')) {
                    $this->insertForeignElement($token, DomNs::Html);
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
                    $this->mode = InsertionMode::AfterHead;
                } else {
                    $anythingElse = $token->oneOf('body', 'html', 'br');
                }
            }
        } else {
            $anythingElse = true;
        }
        if ($anythingElse) {
            $this->stack->pop();
            $this->mode = InsertionMode::AfterHead;
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
                    $this->insertForeignElement($token, DomNs::Html);
                    $this->mode = InsertionMode::InBody;
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
            $this->insertForeignElement(new TagToken('body', true), DomNs::Html);
            $this->mode = InsertionMode::InBody;
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
                    $this->insertForeignElement($token, DomNs::Html);
                    $this->lexer->trimNextLf = true;
                } elseif ($token->name === 'image') {
                    $token->name = 'img';
                    $this->insertForeignElement($token, DomNs::Html);
                } elseif ($token->name === 'textarea') {
                    $this->insertForeignElement($token, DomNs::Html);
                    $text = $this->lexer->consumeRcDataText($token->name);
                    if (($text[0] ?? '') === "\n") {
                        $text = substr($text, 1);
                    }
                    $this->insertText($text);
                    $this->stack->pop();
                } elseif ($token->oneOf('xmp', 'iframe', 'noembed', 'noscript')) {
                    $this->insertForeignElement($token, DomNs::Html);
                    $this->insertText($this->lexer->consumeRawText($token->name));
                    $this->stack->pop();
                } elseif ($token->name === 'math') {
                    $this->insertForeignElement($token, DomNs::MathMl);
                } elseif ($token->name === 'svg') {
                    $this->insertForeignElement($token, DomNs::Svg);
                } elseif ($token->oneOf('head')) {
                    // Ignore
                } else {
                    $this->insertForeignElement($token, DomNs::Html, !ElementNode::isVoid($token->name));
                }
            } else {
                if ($token->name === 'body') {
                    $this->mode = InsertionMode::AfterBody;
                } elseif ($token->name === 'html') {
                    $this->mode = InsertionMode::AfterBody;
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
                    $this->mode = InsertionMode::AfterAfterBody;
                } else {
                    $anythingElse = true;
                }
            }
        } else { // EofToken
            // Stop parsing
        }
        if ($anythingElse) {
            $this->mode = InsertionMode::InBody;
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
            $this->mode = InsertionMode::InBody;
            $this->runInBodyInsertionMode($token);
        }
    }

    #endregion

    protected function createElement(TagToken $token, DomNs $ns = DomNs::Html): ElementNode
    {
        $element = new ElementNode($token->name, $ns);
        if ($ns === DomNs::Html) {
            foreach ($token->attributes as $name => $value) {
                $element->attributes()->set($name, $value);
            }
        } else {
            if ($ns === DomNs::MathMl) {
                foreach ($token->attributes as $name => $value) {
                    $this->adjustMathMlAttrs($name, $value, $element);
                }
            } elseif ($ns === DomNs::Svg) {
                foreach ($token->attributes as $name => $value) {
                    $this->adjustSvgAttrs($name, $value, $element);
                }
            } else {
                foreach ($token->attributes as $name => $value) {
                    $element->attributes()->set($name, $value);
                }
            }
        }
        return $element;
    }

    protected function fillMissingAttrs(TagToken $token, ElementNode $element): void
    {
        foreach ($token->attributes as $name => $value) {
            if ($element->attributes()->getNamedItem($name) === null) {
                $element->attributes()->set($name, $value);
            }
        }
    }

    protected function insertComment(CommentToken $token): void
    {
        $comment = new CommentNode($token->value);
        $parent = $this->stack->current();
        $parent->nodeList->simAppend($comment);
    }

    protected function insertForeignElement(TagToken $token, DomNs $ns, bool $pushToStack = true): ElementNode
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
        if ($element->namespaceURI() === DomNs::MathMl && $element->localName() === 'annotation-xml') {
            $encoding = $element->getAttribute('encoding');
            if ($encoding) {
                $encoding = strtolower($encoding);
            }
            return $encoding === 'text/html' || $encoding === 'application/xhtml+xml';
        } elseif ($element->namespaceURI() === DomNs::Svg) {
            return in_array($element->localName(), ['foreignObject', 'desc', 'title'], true);
        } else {
            return false;
        }
    }

    protected function isMathMlTextIntegrationPoint(ElementNode $element): bool
    {
        return $element->namespaceURI() === DomNs::MathMl
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
        match ($name) {
            'xlink:actuate' => $attrs->setNs(DomNs::XLink, 'xlink', 'actuate', $value),
            'xlink:arcrole' => $attrs->setNs(DomNs::XLink, 'xlink', 'arcrole', $value),
            'xlink:href' => $attrs->setNs(DomNs::XLink, 'xlink', 'href', $value),
            'xlink:role' => $attrs->setNs(DomNs::XLink, 'xlink', 'role', $value),
            'xlink:show' => $attrs->setNs(DomNs::XLink, 'xlink', 'show', $value),
            'xlink:title' => $attrs->setNs(DomNs::XLink, 'xlink', 'title', $value),
            'xlink:type' => $attrs->setNs(DomNs::XLink, 'xlink', 'type', $value),
            'xml:lang' => $attrs->setNs(DomNs::Xml, 'xml', 'lang', $value),
            'xml:space' => $attrs->setNs(DomNs::Xml, 'xml', 'space', $value),
            'xmlns' => $attrs->setNs(DomNs::XmlNs, null, 'xmlns', $value),
            'xmlns:xlink' => $attrs->setNs(DomNs::XmlNs, 'xmlns', 'xlink', $value),
            default => $attrs->set($name, $value),
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
        $adjustedName = match ($name) {
            'attributename' => 'attributeName',
            'attributetype' => 'attributeType',
            'basefrequency' => 'baseFrequency',
            'baseprofile' => 'baseProfile',
            'calcmode' => 'calcMode',
            'clippathunits' => 'clipPathUnits',
            'diffuseconstant' => 'diffuseConstant',
            'edgemode' => 'edgeMode',
            'filterunits' => 'filterUnits',
            'glyphref' => 'glyphRef',
            'gradienttransform' => 'gradientTransform',
            'gradientunits' => 'gradientUnits',
            'kernelmatrix' => 'kernelMatrix',
            'kernelunitlength' => 'kernelUnitLength',
            'keypoints' => 'keyPoints',
            'keysplines' => 'keySplines',
            'keytimes' => 'keyTimes',
            'lengthadjust' => 'lengthAdjust',
            'limitingconeangle' => 'limitingConeAngle',
            'markerheight' => 'markerHeight',
            'markerunits' => 'markerUnits',
            'markerwidth' => 'markerWidth',
            'maskcontentunits' => 'maskContentUnits',
            'maskunits' => 'maskUnits',
            'numoctaves' => 'numOctaves',
            'pathlength' => 'pathLength',
            'patterncontentunits' => 'patternContentUnits',
            'patterntransform' => 'patternTransform',
            'patternunits' => 'patternUnits',
            'pointsatx' => 'pointsAtX',
            'pointsaty' => 'pointsAtY',
            'pointsatz' => 'pointsAtZ',
            'preservealpha' => 'preserveAlpha',
            'preserveaspectratio' => 'preserveAspectRatio',
            'primitiveunits' => 'primitiveUnits',
            'refx' => 'refX',
            'refy' => 'refY',
            'repeatcount' => 'repeatCount',
            'repeatdur' => 'repeatDur',
            'requiredextensions' => 'requiredExtensions',
            'requiredfeatures' => 'requiredFeatures',
            'specularconstant' => 'specularConstant',
            'specularexponent' => 'specularExponent',
            'spreadmethod' => 'spreadMethod',
            'startoffset' => 'startOffset',
            'stddeviation' => 'stdDeviation',
            'stitchtiles' => 'stitchTiles',
            'surfacescale' => 'surfaceScale',
            'systemlanguage' => 'systemLanguage',
            'tablevalues' => 'tableValues',
            'targetx' => 'targetX',
            'targety' => 'targetY',
            'textlength' => 'textLength',
            'viewbox' => 'viewBox',
            'viewtarget' => 'viewTarget',
            'xchannelselector' => 'xChannelSelector',
            'ychannelselector' => 'yChannelSelector',
            'zoomandpan' => 'zoomAndPan',
            default => false,
        };
        if ($adjustedName === false) {
            $this->adjustForeignAttrs($name, $value, $element);
        } else {
            $element->attributes()->set($adjustedName, $value);
        }
    }

    #endregion
}
