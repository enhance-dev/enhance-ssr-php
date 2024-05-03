# Enhance SSR PHP 

A library for server rendering web components in PHP that is compatible with Enhance SSR ([Enhance.dev](https://enhance.dev)).

## Runtime: WASM or native PHP

This package includes both a WASM and native PHP version of enhance ssr. The WASM version allows component definitions written in JavaScript.
The examples directory includes examples of both versions.

## Install
This package can be managed and installed with Composer:

```sh
composer require enhance-dev/ssr
```
## Run Examples
To run the native and WASM examples run `composer serve-native` or `composer serve-wasm` respectively. 


## Usage:
See usage examples for native PHP and WASM in the examples directory. 

### Native PHP

```php

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

```

### WASM 

```php

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

```




## Install Extism Runtime Dependency (for WASM only)

For the WASM version there are additional requirements. 
For this library, you first need to install the Extism Runtime by following the instructions in the [PHP SDK Repository](https://github.com/extism/php-sdk#install-the-extism-runtime-dependency).


## Acknowledgements

Thank you @mariohamann for prototyping a PHP example in using [Extism](https://extism.org) https://github.com/mariohamann/enhance-ssr-wasm/tree/experiment/extism.
