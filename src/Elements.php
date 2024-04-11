<?php

namespace Enhance;
use Exception;

class Elements
{
    private $elements = [];
    public $wasmElements = null;

    public function __construct($path = null, $options = null)
    {
        if ($options && $options["wasm"] === true && $path && is_dir($path)) {
            $dirHandle = opendir($path);
            if ($dirHandle) {
                while (($filename = readdir($dirHandle)) !== false) {
                    $filePath = $path . "/" . $filename;
                    if (is_file($filePath)) {
                        $key = pathinfo($filename, PATHINFO_FILENAME);
                        $content = file_get_contents($filePath);
                        $this->wasmElements[$key] = $content;
                    }
                }
                closedir($dirHandle);
            }
            return;
        } elseif ($path && is_dir($path)) {
            // Load PHP files
            foreach (glob("$path/*.php") as $file) {
                require_once $file;
                $functionName = $this->convertToSnakeCase(
                    basename($file, ".php")
                );
                $camelFunctionName = $this->convertToCamelCase(
                    basename($file, ".php")
                );
                $pascalFunctionName = ucfirst($camelFunctionName);
                if (function_exists($functionName)) {
                    $this->elements[$functionName] = $functionName;
                } elseif (function_exists($camelFunctionName)) {
                    $this->elements[$functionName] = $camelFunctionName;
                } elseif (function_exists($pascalFunctionName)) {
                    $this->elements[$functionName] = $pascalFunctionName;
                } else {
                    throw new Exception(
                        "Element function '$functionName' does not exist."
                    );
                }
            }

            // Load HTML files
            foreach (glob("$path/*.html") as $file) {
                $functionName = $this->convertToSnakeCase(
                    basename($file, ".html")
                );
                $this->elements[$functionName] = function ($state = null) use (
                    $file
                ) {
                    return file_get_contents($file);
                };
            }
        }
    }

    public function execute($name, $state = null)
    {
        $functionName = $this->convertToSnakeCase($name);
        if (isset($this->elements[$functionName])) {
            return call_user_func($this->elements[$functionName], $state);
        }
        // Handle the case where the function does not exist
        throw new Exception("Element function '$name' does not exist.");
    }

    private function convertToSnakeCase($name)
    {
        return strtolower(preg_replace("/-/", "_", $name));
    }
    private function convertToCamelCase($name)
    {
        return lcfirst(
            str_replace(" ", "", ucwords(str_replace("-", " ", $name)))
        );
    }

    public function exists($name)
    {
        $functionName = $this->convertToSnakeCase($name);
        return isset($this->elements[$functionName]);
    }
}
