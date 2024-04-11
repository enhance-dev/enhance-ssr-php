<?php

namespace Enhance;
// require "../vendor/autoload.php";
use Exception;
use Extism\Plugin;
use Extism\Manifest;
use Extism\PathWasmSource;

class EnhanceWASM
{
    private $enhance;

    public function __construct($config = [])
    {
        $wasm = new PathWasmSource(
            __DIR__ . "/../vendor/enhance/ssr-wasm/enhance-ssr.wasm"
        );
        $manifest = new Manifest($wasm);
        if (isset($config["elements"])) {
            $manifest->config->elements = json_encode($config["elements"]);
        }
        $this->enhance = new Plugin($manifest, true);
    }

    public function ssr($input)
    {
        $payload = [
            "markup" => $input["markup"] ?? "",
            "initialState" => $input["initialState"] ?? [],
        ];
        if (isset($input["elements"])) {
            $payload["elements"] = $input["elements"];
        }
        $jsonPayload = json_encode($input, JSON_PRETTY_PRINT);
        $output = $this->enhance->call("ssr", $jsonPayload);
        $document = json_decode($output);
        return $document;
    }
}
