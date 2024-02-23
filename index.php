<?php
require __DIR__ . '/vendor/autoload.php';

use Extism\Plugin;
use Extism\Manifest;
use Extism\PathWasmSource;

$wasm = new PathWasmSource(__DIR__ . "/enhance-ssr.wasm");
$manifest = new Manifest($wasm);
$enhance = new Plugin($manifest, true);

function readElements($directory) {
    $elements = [];
    if(is_dir($directory)){
        $dirHandle = opendir($directory);
        
        if ($dirHandle) {
            while (($filename = readdir($dirHandle)) !== false) {
                $filePath = $directory . '/' . $filename;
                if (is_file($filePath)) {
                    $key = pathinfo($filename, PATHINFO_FILENAME);
                    $content = file_get_contents($filePath);
                    $elements[$key] = $content;
                }
            }
            closedir($dirHandle);
        }
    }
    return $elements;
}

$elementPath = './elements';
$elements = readElements($elementPath);

$input = [
  "markup" => "<my-header>Hello World</my-header>",
  "elements" => $elements,
  "initialState" => []
];

$payload = json_encode($input, JSON_PRETTY_PRINT);

$output = $enhance->call("ssr", json_encode($input));

$htmlDocument = json_decode($output)->document;

echo $htmlDocument . "\n";

?>


