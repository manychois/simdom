<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\NamespaceUri;

/**
 * Represents an element node which namespace is not HTML.
 */
class NonHtmlElementNode extends ElementNode
{
    private readonly NamespaceUri $namespace;

    /**
     * Creates an non-HTML element based on the given element node.
     *
     * @param ElementNode  $node      The element node to copy.
     * @param NamespaceUri $namespace The namespace of the element.
     */
    public function __construct(ElementNode $node, NamespaceUri $namespace)
    {
        parent::__construct(self::normaliseTagName($node->localName(), $namespace), false);
        foreach ($node->attrs as $attr) {
            $this->attrs[$attr->name] = new Attr(self::normaliseAttrName($attr->name, $namespace), $attr->value);
        }
        $this->namespace = $namespace;
    }

    #region extends ElementNode

    /**
     * @inheritDoc
     */
    public function namespaceUri(): NamespaceUri
    {
        return $this->namespace;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $name, ?string $value): self
    {
        $index = strtolower($name);
        $attr = $this->attrs[$index] ?? null;
        if ($attr === null) {
            $name = self::normaliseAttrName($index, $this->namespace);
            $this->attrs[$index] = new Attr($name, $value);
        } else {
            $attr->value = $value;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function tagName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $html = '<' . $this->name;
        foreach ($this->attrs as $attr) {
            $html .= ' ' . $attr->toHtml();
        }

        if (count($this->cNodes) === 0) {
            $html .= ' />';

            return $html;
        }

        $html .= '>';
        $html .= AbstractParentNode::toHtml();
        $html .= sprintf('</%s>', $this->name);

        return $html;
    }

    #endregion

    /**
     * Corrects the case of the element name based on the namespace.
     *
     * @param string       $localName The element local name.
     * @param NamespaceUri $namespace The namespace of the element.
     *
     * @return string The corrected elemnt tag name.
     */
    private static function normaliseTagName(string $localName, NamespaceUri $namespace): string
    {
        return match ($namespace) {
            NamespaceUri::Svg => match ($localName) {
                'altglyph' => 'altGlyph',
                'altglyphdef' => 'altGlyphDef',
                'altglyphitem' => 'altGlyphItem',
                'animatecolor' => 'animateColor',
                'animatemotion' => 'animateMotion',
                'animatetransform' => 'animateTransform',
                'clippath' => 'clipPath',
                'feblend' => 'feBlend',
                'fecolormatrix' => 'feColorMatrix',
                'fecomponenttransfer' => 'feComponentTransfer',
                'fecomposite' => 'feComposite',
                'feconvolvematrix' => 'feConvolveMatrix',
                'fediffuselighting' => 'feDiffuseLighting',
                'fedisplacementmap' => 'feDisplacementMap',
                'fedistantlight' => 'feDistantLight',
                'feflood' => 'feFlood',
                'fefunca' => 'feFuncA',
                'fefuncb' => 'feFuncB',
                'fefuncg' => 'feFuncG',
                'fefuncr' => 'feFuncR',
                'fegaussianblur' => 'feGaussianBlur',
                'feimage' => 'feImage',
                'femerge' => 'feMerge',
                'femergenode' => 'feMergeNode',
                'femorphology' => 'feMorphology',
                'feoffset' => 'feOffset',
                'fepointlight' => 'fePointLight',
                'fespecularlighting' => 'feSpecularLighting',
                'fespotlight' => 'feSpotLight',
                'fetile' => 'feTile',
                'feturbulence' => 'feTurbulence',
                'foreignobject' => 'foreignObject',
                'glyphref' => 'glyphRef',
                'lineargradient' => 'linearGradient',
                'radialgradient' => 'radialGradient',
                'textpath' => 'textPath',
                default => $localName,
            },
            default => $localName,
        };
    }

    /**
     * Corrects the case of the attribute name based on the namespace.
     *
     * @param string       $name      The attribute name in lower case.
     * @param NamespaceUri $namespace The namespace of the element.
     *
     * @return string The corrected attribute name.
     */
    private static function normaliseAttrName(string $name, NamespaceUri $namespace): string
    {
        return match ($namespace) {
            NamespaceUri::MathMl => $name === 'definitionurl' ? 'definitionURL' : $name,
            NamespaceUri::Svg => match ($name) {
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
                default => $name,
            },
            default => $name,
        };
    }
}
