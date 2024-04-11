<?php

namespace Enhance;
use Exception;
use Sabberworm\CSS\Parser;

class ShadyStyles
{
    public function __construct()
    {
    }

    public function styleTransform($params)
    {
        $context = $params["context"] ?? "markup";
        $raw = $params["raw"] ?? "";
        $attrs = $params["attrs"] ?? [];
        $tagName = $params["tagName"] ?? "";
        $scope = $attrs["scope"] ?? "";

        if ($scope === "global" && $context === "markup") {
            return $raw;
        } elseif ($scope === "global" && $context === "template") {
            return "";
        } elseif ($context === "markup") {
            return $this->processBlock($raw, $tagName);
        } else {
            // for context === 'template' and any other case
            return $raw;
        }
    }

    private function processBlock($css, $scopeTo)
    {
        if (!$scopeTo) {
            return $css;
        }

        $parser = new \Sabberworm\CSS\Parser($css);
        $doc = $parser->parse();

        foreach ($doc->getAllDeclarationBlocks() as $block) {
            $selectors = $block->getSelectors();
            foreach ($selectors as $selector) {
                $newSelector = $this->selectorConverter(
                    $selector->getSelector(),
                    $scopeTo
                );
                $selector->setSelector($newSelector);
            }
        }
        return $doc->render();
    }

    private function selectorConverter($selector, $scopeTo)
    {
        // Apply transformations
        $selector = preg_replace(
            "/(::slotted)\(\s*(.+?)\s*\)/",
            '$2',
            $selector
        );
        $selector = preg_replace(
            "/(:host-context)\(\s*(.+?)\s*\)/",
            '$2 __TAGNAME__',
            $selector
        );
        $selector = preg_replace(
            "/(:host)\(\s*(.+?)\s*\)/",
            '__TAGNAME__$2',
            $selector
        );
        $selector = preg_replace(
            "/([a-zA-Z0-9_-]*)(::part)\(\s*(.+?)\s*\)/",
            '$1 [part*="$3"][part*="$1"]',
            $selector
        );
        $selector = str_replace(":host", "__TAGNAME__", $selector);

        if (strpos($selector, "__TAGNAME__") !== false) {
            $selector = preg_replace(
                "/(.*)__TAGNAME__(.*)/",
                "$1" . $scopeTo . "$2",
                $selector
            );
        } elseif (strpos($selector, "&") === 0) {
            // No change if it starts with '&'
        } else {
            $selector = "{$scopeTo} {$selector}";
        }

        return $selector;
    }
}
