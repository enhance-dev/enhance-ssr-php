# Enhance SSR PHP Example
This project demonstrates using Enhance to serverside render components in PHP. 

## Install Extism Runtime Dependency
For this library, you first need to install the Extism Runtime by following the instructions in the [PHP SDK Repository](https://github.com/extism/php-sdk#install-the-extism-runtime-dependency).

## Download Enhance SSR wasm
Download the latest release of the compiled wasm and put it in the `src/http/get-index` folder:
```sh
curl -L https://github.com/enhance-dev/enhance-ssr-wasm/releases/download/v0.0.3/enhance-ssr.wasm.gz | gunzip > src/http/get-index/enhance-ssr.wasm
```

## Run
1. Install Dependency
```sh
composer install
```
2. Run Server
```sh
php -d ffi.enable=true -S localhost:8000  
```
3. load http://localhost:8000

## Acknowledgements
Thank you @mariohamann for first prototyping a PHP example in https://github.com/mariohamann/enhance-ssr-wasm/tree/experiment/extism.

