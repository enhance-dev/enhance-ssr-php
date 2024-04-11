<?php
require "../../../vendor/autoload.php";

use Enhance\EnhanceWASM;
use Enhance\Elements;

$elementPath = "../resources";
$elements = new Elements($elementPath, ["wasm" => true]);
$enhance = new EnhanceWASM(["elements" => $elements->wasmElements]);

$input = [
    "markup" => "<my-header>Hello World</my-header>",
    "initialState" => [],
];

$output = $enhance->ssr($input);

$htmlDocument = $output->document;

echo $htmlDocument . "\n";
