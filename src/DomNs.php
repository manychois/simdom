<?php

declare(strict_types=1);

namespace Manychois\Simdom;

enum DomNs: string
{
    case Html = 'http://www.w3.org/1999/xhtml';
    case MathMl = 'http://www.w3.org/1998/Math/MathML';
    case Svg = 'http://www.w3.org/2000/svg';
    case XLink = 'http://www.w3.org/1999/xlink';
    case Xml = 'http://www.w3.org/XML/1998/namespace';
    case XmlNs = 'http://www.w3.org/2000/xmlns/';
}
