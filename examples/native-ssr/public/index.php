<?php
require "../../../vendor/autoload.php";

use Enhance\Enhancer;
use Enhance\Elements;
use Enhance\ShadyStyles;

$elementPath = __DIR__ . "/../resources";
$elements = new Elements($elementPath);
$scopeMyStyle = new ShadyStyles();
$enhance = new Enhancer([
    "elements" => $elements,
    "initialState" => [],
    "styleTransforms" => [[$scopeMyStyle, "styleTransform"]],
    "enhancedAttr" => true,
    "bodyContent" => false,
]);

$htmlString = <<<HTMLDOC
<!DOCTYPE html>
       <html>
       <head>
       </head>
       <body>
           <my-header><h1>Hello World</h1></my-header>
       </body>
       </html>
HTMLDOC;

$output = $enhance->ssr($htmlString);

echo $output;
