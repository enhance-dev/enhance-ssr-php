<?php

namespace Enhance;

use Enhance\Elements;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use DOMText;
use libxml_set_encoding;
// use tidy;

class Enhancer
{
    private $options;
    private $elements;
    private $store;

    public function __construct($options = [])
    {
        $defaultOptions = [
            "elements" => new Elements(),
            "initialState" => [],
            "scriptTransforms" => [],
            "styleTransforms" => [],
            "uuidFunction" => function () {
                return $this->generateRandomString(15);
            },
            "bodyContent" => false,
            "enhancedAttr" => true,
        ];

        if (
            isset($options["elements"]) &&
            $options["elements"] instanceof Elements
        ) {
            $defaultOptions["elements"] = $options["elements"];
        }
        $this->options = array_merge($defaultOptions, $options);
        if (!($this->options["elements"] instanceof Elements)) {
            throw new \Exception(
                "The 'elements' option must be an instance of Elements."
            );
        }
        $this->elements = $this->options["elements"];
        $this->store = $this->options["initialState"];
    }

    public function ssr($htmlString)
    {
        $doc = new DOMDocument(1.0, "UTF-8");
        // $convmap = array(0x80, 0xFFFF, 0, 0xFFFF);
        // $encodedHtml = mb_encode_numericentity($htmlString, $convmap, 'UTF-8');
        // @$doc->loadHTML($encodedHtml, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR );
        @$doc->loadHTML('<?xml encoding="utf-8"?>' . $htmlString);


        $htmlElement = $doc->getElementsByTagName("html")->item(0);
        $bodyElement = $htmlElement
            ? $doc->getElementsByTagName("body")->item(0)
            : null;
        $headElement = $htmlElement
            ? $doc->getElementsByTagName("head")->item(0)
            : null;

        $collected = [
            "collectedStyles" => [],
            "collectedScripts" => [],
            "collectedLinks" => [],
        ];
        $collected = $this->processCustomElements($bodyElement);
        if ($bodyElement) {
            if (count($collected["collectedScripts"]) > 0) {
                $flattenedScripts = $this->flattenArray(
                    $collected["collectedScripts"]
                );
                $uniqueScripts = $this->uniqueTags($flattenedScripts);

                $this->appendNodes($bodyElement, $uniqueScripts);
            }

            if ($this->options["bodyContent"]) {
                $bodyContents = "";
                foreach ($bodyElement->childNodes as $childNode) {
                    $bodyContents .= $doc->saveHTML($childNode);
                }
                return $bodyContents;
            }
        }
        if ($headElement && !$this->options["bodyContent"]) {
            if (count($collected["collectedStyles"]) > 0) {
                $flattenedStyles = $this->flattenArray(
                    $collected["collectedStyles"]
                );
                $uniqueStyles = $this->uniqueTags($flattenedStyles);
                $cssBlocks = [];
                foreach ($uniqueStyles as $style) {
                    $cssBlocks[] = $style->childNodes[0]->nodeValue;
                }
                $values = array_values($cssBlocks);
                usort($values, function ($a, $b) {
                    $aStart = substr(trim($a), 0, 7);
                    $bStart = substr(trim($b), 0, 7);
                    if ($aStart === "@import" && $bStart !== "@import") {
                        return -1;
                    }
                    if ($aStart !== "@import" && $bStart === "@import") {
                        return 1;
                    }
                    return 0;
                });
                $mergedCssString = implode("\n", $values);
                $mergedStyles = $mergedCssString ? 
                  "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><style>{$mergedCssString}</style></head><body></body></html>" 
                  : "";

                $tempDoc = new DOMDocument(1.0, "UTF-8");
                $tempDoc->loadHTML($mergedStyles, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                $loadedStyle = $tempDoc->getElementsByTagName("style")->item(0);
                $importedStyles = $headElement->ownerDocument->importNode(
                    $loadedStyle,
                    true
                );
                $headElement->appendChild($importedStyles);
            }
        }

        if ($headElement && !$this->options["bodyContent"]) {
            if (count($collected["collectedLinks"]) > 0) {
                $flattenedLinks = $this->flattenArray(
                    $collected["collectedLinks"]
                );
                $uniqueLinks = $this->uniqueAttributeSet($flattenedLinks);

                $this->appendNodes($headElement, $uniqueLinks);
            }
        }

        $doc->encoding = "UTF-8";
        return "<!DOCTYPE html>" . $doc->saveHTML($doc->documentElement);
    }

    private function processCustomElements(&$node)
    {
        $collectedStyles = [];
        $collectedScripts = [];
        $collectedLinks = [];
        $context = [];

        $this->walk($node, function ($child) use (
            &$collectedStyles,
            &$collectedScripts,
            &$collectedLinks,
            &$context
        ) {
            if (
                $child instanceof DOMElement &&
                $this->isCustomElement($child->tagName)
            ) {
                if ($this->elements->exists($child->tagName)) {
                    $expandedTemplate = $this->expandTemplate([
                        "node" => $child,
                        "elements" => $this->elements,
                        "state" => [
                            "context" => $context,
                            "instanceID" => $this->options["uuidFunction"](),
                            "store" => $this->store,
                        ],
                        "styleTransforms" => $this->options["styleTransforms"],
                        "scriptTransforms" =>
                            $this->options["scriptTransforms"],
                    ]);

                    if ($this->options["enhancedAttr"] === true) {
                        $child->setAttribute("enhanced", "✨");
                    }

                    $collectedScripts = array_merge(
                        $collectedScripts,
                        $expandedTemplate["scripts"]
                    );
                    $collectedStyles = array_merge(
                        $collectedStyles,
                        $expandedTemplate["styles"]
                    );
                    $collectedLinks = array_merge(
                        $collectedLinks,
                        $expandedTemplate["links"]
                    );

                    $this->fillSlots($expandedTemplate["frag"], $child);
                    $importedFrag = $child->ownerDocument->importNode(
                        $expandedTemplate["frag"],
                        true
                    );
                    // $child->appendChild($importedFrag);
                    // return $child;
                }
            }
        });

        return [
            "collectedStyles" => $collectedStyles,
            "collectedScripts" => $collectedScripts,
            "collectedLinks" => $collectedLinks,
        ];
    }

    // For Debugging
    private function printNodes($nodeArray)
    {
        $array = $nodeArray;
        if ($nodeArray instanceof DOMNodeList) {
            $array = [];
            foreach ($nodeArray as $item) {
                $array[] = $item;
            }
        }
        return array_map(function ($node) {
            return $node->ownerDocument->saveHTML($node);
        }, $array);
    }

    private function fillSlots($template, $node)
    {
        $slots = $this->findSlots($template);
        $inserts = $this->findInserts($node);

        $usedSlots = [];
        $usedInserts = [];
        $unnamedSlots = [];

        foreach ($slots as $slot) {
            $hasSlotName = false;
            $slotName = $slot->getAttribute("name");

            if ($slotName) {
                $hasSlotName = true;
                foreach ($inserts as $insert) {
                    $insertSlot = $insert->getAttribute("slot");
                    if ($insertSlot === $slotName) {
                        if ($slot->parentNode) {
                            $importedInsert = $slot->ownerDocument->importNode(
                                $insert,
                                true
                            );
                            $slot->parentNode->replaceChild(
                                $importedInsert,
                                $slot
                            );
                        }
                        $usedSlots[] = $slot;
                        $usedInserts[] = $insert;
                    }
                }
            }
            if (!$hasSlotName) {
                $unnamedSlots[] = $slot;
            }
        }

        foreach ($unnamedSlots as $slot) {
            $unnamedChildren = [];
            foreach ($node->childNodes as $child) {
                if (!in_array($child, $usedInserts, true)) {
                    $unnamedChildren[] = $child;
                }
            }

            $slotDocument = $slot->ownerDocument;
            $slotParent = $slot->parentNode;
            if (count($unnamedChildren)) {
              foreach ($unnamedChildren as $child) {
                $importedNode = $slotDocument->importNode($child, true);
                $slotParent->insertBefore($importedNode, $slot);
              }
            }  else {
              foreach (iterator_to_array($slot->childNodes) as $child) {
                $slotParent->insertBefore($child, $slot);
              }
            }
            $slotParent->removeChild($slot);
        }
        $unusedSlots = [];
        foreach ($slots as $slot) {
            $isUsed = false;
            foreach ($usedSlots as $usedSlot) {
                if ($slot->isSameNode($usedSlot)) {
                    $isUsed = true;
                    break;
                }
            }
            if (!$isUsed) {
                $unusedSlots[] = $slot;
            }
        }
        $this->replaceSlots($template, $unusedSlots);
        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }

        foreach ($template->childNodes as $childNode) {
            $importedNode = $node->ownerDocument->importNode($childNode, true);
            $node->appendChild($importedNode);
        }
    }

    private function findSlots(DOMNode $node)
    {
        $xpath = new DOMXPath($node->ownerDocument);
        $slots = $xpath->query(".//slot", $node);
        $slotArray = [];
        foreach ($slots as $slot) {
            $slotArray[] = $slot;
        }
        return $slotArray;
        return $slots;
    }

    private function findInserts(DOMNode $node)
    {
        $inserts = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->hasAttribute("slot")) {
                $inserts[] = $child;
            }
        }
        return $inserts;
    }

    private function replaceSlots(DOMNode $node, $slots)
    {
        foreach ($slots as $slot) {
            $value = $slot->getAttribute("name");
            $asTag = $slot->hasAttribute("as")
                ? $slot->getAttribute("as")
                : "span";

            $slotChildren = [];
            foreach ($slot->childNodes as $child) {
                if (!($child instanceof DOMText)) {
                    $slotChildren[] = $child;
                }
            }

            if ($value) {
                $doc = $slot->ownerDocument;
                $wrapper = $doc->createElement($asTag);
                $wrapper->setAttribute("slot", $value);

                if (count($slotChildren) === 0 || count($slotChildren) > 1) {
                    foreach ($slot->childNodes as $child) {
                        $wrapper->appendChild($child->cloneNode(true));
                    }
                } elseif (count($slotChildren) === 1) {
                    $slotChildren[0]->setAttribute("slot", $value);
                    $slot->parentNode->insertBefore(
                        $slotChildren[0]->cloneNode(true),
                        $slot
                    );
                }

                if ($wrapper->hasChildNodes()) {
                    $slot->parentNode->replaceChild($wrapper, $slot);
                } else {
                    $slot->parentNode->removeChild($slot);
                }
            }
        }
        return $node;
    }

    public function expandTemplate($params)
    {
        $node = $params["node"];
        $elements = $params["elements"];
        $state = $params["state"];
        $styleTransforms = $params["styleTransforms"];
        $scriptTransforms = $params["scriptTransforms"];
        $tagName = $node->tagName;
        $frag = $this->renderTemplate([
            "name" => $tagName,
            "elements" => $elements,
            "attrs" => $this->getNodeAttributes($node),
            "state" => $state,
        ]);

        $styles = [];
        $scripts = [];
        $links = [];

        foreach ($frag->childNodes as $childNode) {
            if ($childNode->nodeName === "script") {
                $transformedScript = $this->applyScriptTransforms([
                    "node" => $childNode,
                    "scriptTransforms" => $scriptTransforms,
                    "tagName" => $tagName,
                ]);
                if ($transformedScript) {
                    $scripts[] = $transformedScript;
                }
            } elseif ($childNode->nodeName === "style") {
                $transformedStyle = $this->applyStyleTransforms([
                    "node" => $childNode,
                    "styleTransforms" => $styleTransforms,
                    "tagName" => $tagName,
                    "context" => "markup",
                ]);
                if ($transformedStyle) {
                    $styles[] = $transformedStyle;
                }
            } elseif ($childNode->nodeName === "link") {
                $links[] = $childNode;
            }
        }

        foreach (array_merge($scripts, $styles, $links) as $part) {
            $part->parentNode->removeChild($part);
        }

        return [
            "frag" => $frag,
            "styles" => $styles,
            "scripts" => $scripts,
            "links" => $links,
        ];
    }

    private function applyScriptTransforms($params)
    {
        $node = $params["node"];
        $scriptTransforms = $params["scriptTransforms"];
        $tagName = $params["tagName"];

        $attrs = $this->getNodeAttributes($node);

        if ($node->hasChildNodes()) {
            $raw = $node->firstChild->nodeValue;
            $out = $raw;
            foreach ($scriptTransforms as $transform) {
                $out = $transform([
                    "attrs" => $attrs,
                    "raw" => $out,
                    "tagName" => $tagName,
                ]);
            }

            if (!empty($out)) {
                $node->firstChild->nodeValue = $out;
            }
        }
        return $node;
    }

    private function applyStyleTransforms($params)
    {
        $node = $params["node"];
        $styleTransforms = $params["styleTransforms"];
        $tagName = $params["tagName"];
        $context = $params["context"] ?? "";

        $attrs = $this->getNodeAttributes($node);

        if ($node->hasChildNodes()) {
            $raw = $node->firstChild->nodeValue;
            $out = $raw;
            foreach ($styleTransforms as $transform) {
                $out = $transform([
                    "attrs" => $attrs,
                    "raw" => $out,
                    "tagName" => $tagName,
                    "context" => $context,
                ]);
            }
            if (!empty($out)) {
                $node->firstChild->nodeValue = $out;
            }
        }
        return $node;
    }

    private static function appendNodes($target, $nodes)
    {
        foreach ($nodes as $node) {
            $importedNode = $target->ownerDocument->importNode($node, true);
            $target->appendChild($importedNode);
        }
    }

    private static function getNodeAttributes($node)
    {
        $attrs = [];
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $attrs[$attr->nodeName] = $attr->nodeValue;
            }
        }
        return $attrs;
    }


    public function renderTemplate($params)
    {
        $name = $params["name"];
        $elements = $params["elements"];
        $attrs = $params["attrs"] ?? [];
        $state = $params["state"] ?? [];

        $state["attrs"] = $attrs;
        $doc = new DOMDocument(1.0, "UTF-8");
        $rendered = $elements->execute($name, $state);
        libxml_use_internal_errors(true); 
    $cleanRendered = <<<HTMLDOC
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
      </head>
    <body><template>{$rendered}</template></body>
    </html>
    HTMLDOC;

        $doc->encoding = "UTF-8";
        $doc->loadHTML(
            $cleanRendered,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $fragment = $doc->createDocumentFragment();

        $template = $doc->getElementsByTagName("template")->item(0);
        $children = iterator_to_array($template->childNodes);
        foreach ($children as $child) {
            $fragment->appendChild($child);
        }
        return $fragment;
    }

    private function walk($node, $callback)
    {
        if ($callback($node) === false) {
            return false;
        }
        foreach ($node->childNodes as $childNode) {
            if ($this->walk($childNode, $callback) === false) {
                return false;
            }
        }
    }

    private static function generateRandomString($length = 10)
    {
        return bin2hex(random_bytes(intdiv($length, 2)));
    }

    public static function isCustomElement($tagName)
    {
        $regex = '/^[a-z]
        [-.0-9_a-z\p{Pc}\p{Pd}\p{Mn}\x{00B7}\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{037D}\x{037F}-\x{1FFF}\x{200C}-\x{200D}\x{203F}-\x{2040}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}]*
        -
        [-.0-9_a-z\p{Pc}\p{Pd}\p{Mn}\x{00B7}\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{037D}\x{037F}-\x{1FFF}\x{200C}-\x{200D}\x{203F}-\x{2040}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}]*
        $/ux';
        $reservedTags = [
            "annotation-xml",
            "color-profile",
            "font-face",
            "font-face-src",
            "font-face-uri",
            "font-face-format",
            "font-face-name",
            "missing-glyph",
        ];

        if (in_array($tagName, $reservedTags)) {
            return false;
        }

        return preg_match($regex, $tagName) === 1;
    }

    private function uniqueAttributeSet($tags)
    {
        if (count($tags, COUNT_RECURSIVE) > 0) {
            $hashTable = [];
            foreach ($tags as $tagNode) {
                $attributes = $this->getNodeAttributes($tagNode);
                ksort($attributes);
                $attributesString = json_encode($attributes);
                $hash = md5($attributesString);
                if (!array_key_exists($hash, $hashTable)) {
                    $hashTable[$hash] = $tagNode;
                }
            }
            return array_values($hashTable);
        } else {
            return $tags;
        }
    }
    private static function uniqueTags($tags)
    {
        if (count($tags, COUNT_RECURSIVE) > 0) {
            $hashTable = [];
            foreach ($tags as $tagNode) {
                $tagDoc = $tagNode->ownerDocument;
                $tagString = $tagDoc->saveHTML($tagNode);
                $hash = md5($tagString);
                if (!array_key_exists($hash, $hashTable)) {
                    $hashTable[$hash] = $tagNode;
                }
            }
            return array_values($hashTable);
        } else {
            return $tags;
        }
    }
    private function flattenArray($array, &$flatArray = [])
    {
        foreach ($array as $element) {
            if (is_array($element)) {
                $this->flattenArray($element, $flatArray);
            } else {
                $flatArray[] = $element;
            }
        }
        return $flatArray;
    }
}
