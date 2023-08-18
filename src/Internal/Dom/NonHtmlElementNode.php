<?php

declare(strict_types=1);

namespace Manychois\Simdom\Internal\Dom;

use Manychois\Simdom\Internal\NamespaceUri;

/**
 * Represents an element node which namespace is not HTML.
 */
class NonHtmlElementNode extends ElementNode
{
    private readonly NamespaceUri $ns;

    /**
     * Creates an non-HTML element based on the given element node.
     *
     * @param ElementNode  $node The element node to copy.
     * @param NamespaceUri $ns   The namespace of the element.
     */
    public function __construct(ElementNode $node, NamespaceUri $ns)
    {
        parent::__construct(self::normaliseTagName($node->localName(), $ns));
        foreach ($node->attrs as $attr) {
            $this->attrs[$attr->name] = new Attr(self::normaliseAttrName($attr->name, $ns), $attr->value);
        }
        $this->ns = $ns;
    }

    #region extends ElementNode

    /**
     * @inheritdoc
     */
    public function namespaceUri(): string
    {
        return $this->ns->value;
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $name, ?string $value): void
    {
        $index = strtolower($name);
        $attr = $this->attrs[$index] ?? null;
        if ($attr === null) {
            $name = self::normaliseAttrName($name, $this->ns);
            $this->attrs[$index] = new Attr($name, $value);
        } else {
            $attr->value = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function tagName(): string
    {
        return $this->name;
    }

    #endregion

    /**
     * Corrects the case of the element name based on the namespace.
     *
     * @param string       $localName The element local name.
     * @param NamespaceUri $ns        The namespace of the element.
     *
     * @return string The corrected elemnt tag name.
     */
    private static function normaliseTagName(string $localName, NamespaceUri $ns): string
    {
        return match ($ns) {
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
     * @param string       $name The attribute name in lower case.
     * @param NamespaceUri $ns   The namespace of the element.
     *
     * @return string The corrected attribute name.
     */
    private static function normaliseAttrName(string $name, NamespaceUri $ns): string
    {
        return match ($ns) {
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
