<?php

use Manychois\Simdom\Comment;
use Manychois\Simdom\DocumentType;
use Manychois\Simdom\Dom;
use Manychois\Simdom\Element;
use Manychois\Simdom\Internal\ParentNode;
use Manychois\Simdom\Node;
use Manychois\Simdom\Text;

include __DIR__ . '/vendor/autoload.php';

$client = new \GuzzleHttp\Client();

$url = $_POST['url'] ?? '';
$htmlSnippet = $_POST['htmlSnippet'] ?? '';

$parser = Dom::createParser();

$parseResponse = [
    'success' => false,
    'errorMsg' => '',
    'result' => [],
];
$html = '';
try {
    if ($url) {
        if (!preg_match('#^https?://#', $url)) {
            $url = 'https://' . $url;
        }
        $res = $client->get($url, [
            'max' => 10,
        ]);
        $html = $res->getBody()->getContents();
        $doc = $parser->parseFromString($html);
        $parseResponse['result'] = jsonify($doc);
        $parseResponse['success'] = true;
    } else {
        $doc = $parser->parseFromString($htmlSnippet);
        $parseResponse['result'] = jsonify($doc);
        $parseResponse['success'] = true;
    }
} catch (\Exception $e) {
    $parseResponse['errorMsg'] = $e->getMessage();
}

function jsonify(Node $node): array
{
    if ($node instanceof Comment) {
        return [
            'type' => 'comment',
            'data' => $node->data(),
        ];
    }
    if ($node instanceof DocumentType) {
        return [
            'type' => 'doctype',
            'name' => $node->name(),
            'publicId' => $node->publicId(),
            'systemId' => $node->systemId(),
        ];
    }
    if ($node instanceof Text) {
        return [
            'type' => 'text',
            'data' => $node->data(),
        ];
    }
    if ($node instanceof Element) {
        $item = [
            'type' => 'element',
            'namespaceURI' => $node->namespaceURI(),
            'tagName' => $node->tagName(),
        ];
        if ($node->hasAttributes()) {
            $item['attributes'] = [];
            foreach ($node->attributes() as $attr) {
                $item['attributes'][] = [
                    'namespaceURI' => $attr->namespaceURI(),
                    'prefix' => $attr->prefix(),
                    'localName' => $attr->localName(),
                    'value' => $attr->value(),
                ];
            }
        }
        if ($node->hasChildNodes()) {
            $item['childNodes'] = [];
            foreach ($node->childNodes() as $child) {
                $item['childNodes'][] = jsonify($child);
            }
        }
        return $item;
    }
    $items = [];
    if ($node instanceof ParentNode) {
        foreach ($node->childNodes() as $child) {
            $items[] = jsonify($child);
        }
    }
    return $items;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($parseResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
