<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Parsing;

use Manychois\Simdom\Internal\Dom\DocNode;
use Manychois\Simdom\Internal\Dom\ElementFactory;
use Manychois\Simdom\Internal\Dom\ElementNode;
use Manychois\Simdom\Internal\Dom\TextNode;
use Manychois\Simdom\Internal\Dom\TextOnlyElementNode;
use Manychois\Simdom\NamespaceUri;

/**
 * Represents a HTML DOM parser.
 */
class DomParser
{
    /**
     * @var InsertionMode The current insertion mode.
     */
    public InsertionMode $mode = InsertionMode::Initial;
    public ?ElementNode $headPointer = null;
    /**
     * @var array<int, ElementNode> The stack of open elements.
     */
    public array $stack = [];
    public readonly Lexer $lexer;

    private DocNode $doc;
    private ?ElementNode $context = null;
    private bool $isFragmentMode = false;
    private ElementFactory $eleFactory;
    /**
     * @var array<string, AbstractInsertionLogic> The insertion logics.
     */
    private array $insertionLogics;

    /**
     * Creates a new instance of DomParser.
     */
    public function __construct()
    {
        $this->lexer = new Lexer($this);
        $this->doc = new DocNode(); // dummy doc
        $this->eleFactory = new ElementFactory();

        $insertionLogics = [
            new BeforeHeadInsertionLogic(),
            new InBodyInsertionLogic(),
        ];
        $this->insertionLogics = [];
        foreach ($insertionLogics as $iLogic) {
            $this->insertionLogics[$iLogic->mode->name] = $iLogic;
        }
    }

    /**
     * Returns the current node, i.e. the top node of the open elements stack.
     *
     * @return ElementNode The current element node.
     */
    public function currentNode(): ElementNode
    {
        $current = end($this->stack);
        assert($current instanceof ElementNode, 'The stack of open elements is empty.');

        return $current;
    }

    /**
     * Parses the given HTML string into a document.
     *
     * @param string $html The HTML string to parse.
     *
     * @return DocNode The document node.
     */
    public function parse(string $html): DocNode
    {
        $doc = new DocNode();
        $this->doc = $doc;

        $this->mode = InsertionMode::Initial;
        $this->stack = [];
        $this->headPointer = null;
        $this->isFragmentMode = false;

        $this->lexer->setInput($html);
        while ($this->lexer->tokenize());

        $this->doc = new DocNode(); // assign a dummy doc so that the original doc can be garbage collected
        $this->stack = [];
        $this->headPointer = null;
        $this->lexer->setInput(''); // reset the lexer

        return $doc;
    }

    /**
     * Parses the given HTML string following HTML fragment parsing algorithm
     *
     * @param string      $html    The HTML string to parse.
     * @param ElementNode $context The context element.
     *
     * @return array<int, \Manychois\Simdom\Internal\Dom\AbstractNode> The parsed nodes.
     */
    public function parsePartial(string $html, ElementNode $context): array
    {
        $this->lexer->setInput($html);
        if ($context->namespaceUri() === NamespaceUri::Html) {
            $localName = $context->localName();
            if (TextOnlyElementNode::isTextOnly($localName)) {
                if ($localName === 'title' || $localName === 'textarea') {
                    $text = $this->lexer->tokenizeRcdataText($localName);
                } else {
                    $text = $this->lexer->tokenizeRawText($localName);
                }
                $this->lexer->setInput(''); // reset the lexer

                return [new TextNode($text)];
            }
        }

        $doc = new DocNode();
        $this->doc = $doc;
        $this->context = $context;
        $this->headPointer = null;
        $this->isFragmentMode = true;

        $root = new ElementNode('html');
        $doc->fastAppend($root);
        $this->stack = [$root];

        $this->resetInsertionMode();

        while ($this->lexer->tokenize());

        $this->doc = new DocNode(); // assign a dummy doc so that the original doc can be garbage collected
        $this->context = null;
        $this->headPointer = null;
        $this->stack = [];
        $this->lexer->setInput(''); // reset the lexer

        /** @var array<int, \Manychois\Simdom\Internal\Dom\AbstractNode> $childNodes */
        $childNodes = iterator_to_array($root->childNodes());
        $root->clear();

        return $childNodes;
    }

    /**
     * Receives a token from the tokenizer.
     *
     * @param AbstractToken $token The token to receive.
     */
    public function receiveToken(AbstractToken $token): void
    {
        $this->processTokenByMode($token);
    }

    /**
     * Processes a token according to the insertion mode.
     *
     * @param AbstractToken $token The token to process.
     * @param InsertionMode $mode  The insertion mode to use. If null, the current insertion mode is used.
     */
    public function processTokenByMode(AbstractToken $token, ?InsertionMode $mode = null): void
    {
        if ($mode === null) {
            $mode = $this->mode;
        }
        $name = $mode->name;
        match ($mode) {
            InsertionMode::Initial => $this->runInitialInsertionMode($token),
            InsertionMode::BeforeHtml => $this->runBeforeHtmlInsertionMode($token),
            InsertionMode::BeforeHead => $this->insertionLogics[$name]->run($this, $token),
            InsertionMode::InHead => $this->runInHeadInsertionMode($token),
            InsertionMode::AfterHead => $this->runAfterHeadInsertionMode($token),
            InsertionMode::InBody => $this->insertionLogics[$name]->run($this, $token),
            InsertionMode::AfterBody => $this->runAfterBodyInsertionMode($token),
            InsertionMode::AfterAfterBody => $this->runAfterAfterBodyInsertionMode($token),
            InsertionMode::ForeignContent => $this->runForeignContentInsertionMode($token),
        };
    }

    /**
     * Inserts the attributes of the given token into the given element, if the element does not have the attributes.
     *
     * @param StartTagToken $token The token to get the attributes from.
     * @param ElementNode   $ele   The element to insert the attributes into.
     */
    public function fillMissingAttrs(StartTagToken $token, ElementNode $ele): void
    {
        foreach ($token->node->attributes() as $k => $v) {
            if (!$ele->hasAttribute($k)) {
                $ele->setAttribute($k, $v);
            }
        }
    }

    #region Insertion modes

    /**
     * Inserts a foreign element into the current node.
     *
     * @param StartTagToken $token     The token to get the element from.
     * @param NamespaceUri  $namespace The namespace of the element.
     *
     * @return ElementNode The inserted element.
     */
    public function insertForeignElement(
        StartTagToken $token,
        NamespaceUri $namespace = NamespaceUri::Html
    ): ElementNode {
        $pushToStack = true;
        $element = $this->eleFactory->convertSpecific($token, $namespace, $pushToStack);
        $this->currentNode()->fastAppend($element);

        if ($pushToStack) {
            $this->stack[] = $element;
        }

        return $element;
    }

    /**
     * Runs the initial insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runInitialInsertionMode(AbstractToken $token): void
    {
        $fallback = false;
        if ($token instanceof TextToken) {
            if (ctype_space($token->node->data())) {
                // ignore
            } else {
                $token->node->setData(ltrim($token->node->data()));
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->doc->fastAppend($token->node);
        } elseif ($token instanceof DoctypeToken) {
            $this->doc->fastAppend($token->node);
            $this->mode = InsertionMode::BeforeHtml;
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $this->mode = InsertionMode::BeforeHtml;
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the before html insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runBeforeHtmlInsertionMode(AbstractToken $token): void
    {
        $fallback = false;
        if ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof CommentToken) {
            $this->doc->fastAppend($token->node);
        } elseif ($token instanceof TextToken) {
            if (ctype_space($token->node->data())) {
                // ignore
            } else {
                $token->node->setData(ltrim($token->node->data()));
                $fallback = true;
            }
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->doc->fastAppend($token->node);
                $this->stack[] = $token->node;
                $this->mode = InsertionMode::BeforeHead;
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            $fallback = $token->isOneOf('head', 'body', 'html', 'br');
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $eHtml = new ElementNode('html');
            $this->doc->fastAppend($eHtml);
            $this->stack[] = $eHtml;
            $this->mode = InsertionMode::BeforeHead;
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the in head insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runInHeadInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        $normalAction = function (): void {
            array_pop($this->stack);
            $this->mode = InsertionMode::AfterHead;
        };

        if ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->currentNode()->fastAppend(new TextNode($matches[1]));
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->currentNode()->fastAppend($token->node);
        } elseif ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->processTokenByMode($token, InsertionMode::InBody);
            } elseif ($token->isOneOf('base', 'basefont', 'bgsound', 'command', 'link', 'meta')) {
                $this->insertForeignElement($token);
            } elseif ($token->node->localName() === 'title') {
                $eTitle = $this->insertForeignElement($token);
                $eTitle->fastAppend(new TextNode($this->lexer->tokenizeRcdataText('title')));
            } elseif ($token->isOneOf('noframes', 'noscript', 'script', 'style', 'template')) {
                $ele = $this->insertForeignElement($token);
                $ele->fastAppend(new TextNode($this->lexer->tokenizeRawText($ele->localName())));
            } elseif ($token->node->localName() === 'head') {
                // ignore
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'head') {
                $normalAction();
            } else {
                $fallback = $token->isOneOf('body', 'html', 'br');
            }
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $normalAction();
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the after head insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runAfterHeadInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        $normalAction = function (StartTagToken $bodyTag): void {
            $this->insertForeignElement($bodyTag);
            $this->mode = InsertionMode::InBody;
        };

        if ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->currentNode()->fastAppend(new TextNode($matches[1]));
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->currentNode()->fastAppend($token->node);
        } elseif ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->processTokenByMode($token, InsertionMode::InBody);
            } elseif ($token->node->localName() === 'body') {
                $normalAction($token);
            } elseif (
                $token->isOneOf(
                    'base',
                    'basefont',
                    'bgsound',
                    'link',
                    'meta',
                    'noframes',
                    'script',
                    'style',
                    'template',
                    'title',
                )
            ) {
                assert($this->headPointer !== null);
                $this->stack[] = $this->headPointer;
                $this->runInHeadInsertionMode($token);
                array_pop($this->stack);
            } elseif ($token->node->localName() === 'head') {
                // ignore
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            $fallback = $token->isOneOf('body', 'html', 'br');
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $normalAction(new StartTagToken('body'));
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the after body insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runAfterBodyInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        if ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->processTokenByMode(new TextToken($matches[1]), InsertionMode::InBody);
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof CommentToken) {
            $this->stack[0]->fastAppend($token->node);
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->processTokenByMode($token, InsertionMode::InBody);
            } else {
                $fallback = true;
            }
        } elseif ($token instanceof EndTagToken) {
            if ($token->tagName === 'html') {
                if ($this->isFragmentMode) {
                    // ignore
                } else {
                    $this->mode = InsertionMode::AfterAfterBody;
                }
            } else {
                $fallback = true;
            }
        } elseif ($token->type === TokenType::Eof) {
            // stop parsing
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $this->mode = InsertionMode::InBody;
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the after after body insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runAfterAfterBodyInsertionMode(AbstractToken $token): void
    {
        $fallback = false;

        if ($token instanceof CommentToken) {
            $this->doc->fastAppend($token->node);
        } elseif ($token instanceof DoctypeToken) {
            $this->processTokenByMode($token, InsertionMode::InBody);
        } elseif ($token instanceof TextToken) {
            preg_match('/^(\s*)(.*)$/s', $token->node->data(), $matches);
            if ($matches[1] !== '') {
                $this->processTokenByMode(new TextToken($matches[1]), InsertionMode::InBody);
            }
            if ($matches[2] !== '') {
                $token->node->setData($matches[2]);
                $fallback = true;
            }
        } elseif ($token instanceof StartTagToken) {
            if ($token->node->localName() === 'html') {
                $this->processTokenByMode($token, InsertionMode::InBody);
            } else {
                $fallback = true;
            }
        } elseif ($token->type === TokenType::Eof) {
            // stop parsing
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $this->mode = InsertionMode::InBody;
            $this->processTokenByMode($token);
        }
    }

    /**
     * Runs the foreign content insertion mode.
     *
     * @param AbstractToken $token The token to process.
     */
    private function runForeignContentInsertionMode(AbstractToken $token): void
    {
        $inBodyMode = false;
        $current = $this->currentNode();
        if ($current->namespaceUri() === NamespaceUri::Html) {
            $inBodyMode = true;
        } elseif ($token instanceof StartTagToken) {
            $tagName = $token->node->localName();
            if ($tagName !== 'mglyph' && $tagName !== 'malignmark' && $this->isMathMlTextIntegrationPoint()) {
                $inBodyMode = true;
            } elseif (
                $tagName === 'svg' && $current->namespaceUri() === NamespaceUri::MathMl &&
                $current->localName() === 'annotation-xml'
            ) {
                $inBodyMode = true;
            } elseif ($this->isHtmlIntegrationPoint()) {
                $inBodyMode = true;
            }
        } elseif ($token->type === TokenType::Text && $this->isHtmlIntegrationPoint()) {
            $inBodyMode = true;
        } elseif ($token->type === TokenType::Eof) {
            $inBodyMode = true;
        }

        if ($inBodyMode) {
            $this->processTokenByMode($token, InsertionMode::InBody);
            if ($this->mode === InsertionMode::ForeignContent) {
                $this->resetInsertionMode();
            }

            return;
        }

        if ($token instanceof TextToken) {
            $token->node->setData(str_replace("\0", "\u{fffd}", $token->node->data()));
            $current->fastAppend($token->node);
        } elseif ($token instanceof CommentToken) {
            $current->fastAppend($token->node);
        } elseif ($token->type === TokenType::Doctype) {
            // ignore
        } elseif ($token instanceof StartTagToken) {
            $popUntilHtml = false;
            $tagName = $token->node->localName();
            if (
                in_array($tagName, [
                    'b',
                    'big',
                    'blockquote',
                    'body',
                    'br',
                    'center',
                    'code',
                    'dd',
                    'div',
                    'dl',
                    'dt',
                    'em',
                    'embed',
                    'h1',
                    'h2',
                    'h3',
                    'h4',
                    'h5',
                    'h6',
                    'head',
                    'hr',
                    'i',
                    'img',
                    'li',
                    'listing',
                    'menu',
                    'meta',
                    'nobr',
                    'ol',
                    'p',
                    'pre',
                    'ruby',
                    's',
                    'small',
                    'span',
                    'strong',
                    'strike',
                    'sub',
                    'sup',
                    'table',
                    'tt',
                    'u',
                    'ul',
                    'var',
                ], true)
            ) {
                $popUntilHtml = true;
            } elseif ($tagName === 'font') {
                foreach (['color', 'face', 'size'] as $attrName) {
                    if ($token->node->hasAttribute($attrName)) {
                        $popUntilHtml = true;
                        break;
                    }
                }
            }

            if ($popUntilHtml) {
                while (true) {
                    array_pop($this->stack);
                    if ($this->currentNode()->namespaceUri() === NamespaceUri::Html) {
                        break;
                    }
                    if ($this->isMathMlTextIntegrationPoint() || $this->isHtmlIntegrationPoint()) {
                        break;
                    }
                }
                $this->resetInsertionMode();
                $this->processTokenByMode($token);
            } else {
                $this->insertForeignElement($token, $current->namespaceUri());
            }
        } elseif ($token instanceof EndTagToken) {
            for ($i = count($this->stack) - 1; $i >= 0; $i--) {
                $node = $this->stack[$i];
                if (strcasecmp($node->localName(), $token->tagName) === 0) {
                    array_splice($this->stack, $i);
                    break;
                }

                if ($node->namespaceUri() === NamespaceUri::Html) {
                    $this->processTokenByMode($token, InsertionMode::InBody);
                    break;
                }
            }

            if ($this->mode === InsertionMode::ForeignContent) {
                $this->resetInsertionMode();
            }
        }
    }

    #endregion

    /**
     * Checks if the current node is an HTML integration point.
     *
     * @return bool True if the current node is an HTML integration point, false otherwise.
     */
    private function isHtmlIntegrationPoint(): bool
    {
        $current = $this->currentNode();
        $nameSpace = $current->namespaceUri();
        if ($nameSpace === NamespaceUri::MathMl && $current->localName() === 'annotation-xml') {
            $encoding = strtolower($current->getAttribute('encoding') ?? '');

            return $encoding === 'text/html' || $encoding === 'application/xhtml+xml';
        }

        if ($nameSpace === NamespaceUri::Svg) {
            return in_array($current->localName(), ['foreignObject', 'desc', 'title'], true);
        }

        return false;
    }

    /**
     * Checks if the current node is a MathML text integration point.
     *
     * @return bool True if the current node is a MathML text integration point, false otherwise.
     */
    private function isMathMlTextIntegrationPoint(): bool
    {
        $current = $this->currentNode();

        return $current->namespaceUri() === NamespaceUri::MathMl &&
            in_array($current->localName(), ['mi', 'mo', 'mn', 'ms', 'mtext'], true);
    }

    /**
     * Resets the insertion mode based on the stack of open elements.
     */
    private function resetInsertionMode(): void
    {
        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if ($i === 0) { // must be fragment case
                assert($this->context !== null, 'The context element must be set.');
                $node = $this->context;
            } else {
                $node = $this->stack[$i];
            }

            $tagName = $node->tagName();
            if ($tagName === 'HEAD' || $tagName === 'BODY') {
                $this->mode = InsertionMode::InBody;

                return;
            }

            if ($tagName === 'HTML') {
                $this->mode = InsertionMode::BeforeHead;

                return;
            }

            if (
                $node->namespaceUri() === NamespaceUri::Svg || $node->namespaceUri() === NamespaceUri::MathMl
            ) {
                $this->mode = InsertionMode::ForeignContent;

                return;
            }
        }
        $this->mode = InsertionMode::InBody;
    }
}
