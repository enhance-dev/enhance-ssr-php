# Enhance SSR PHP Example

This project demonstrates using Enhance to serverside render components in PHP.

## Install Extism Runtime Dependency

For this library, you first need to install the Extism Runtime by following the instructions in the [PHP SDK Repository](https://github.com/extism/php-sdk#install-the-extism-runtime-dependency).

## Run

1. Install dependencies

```sh
composer install
```

2. Run Server

```sh
composer serve
```

3. load http://localhost:8000

## Downloading Enhance SSR wasm

In future releases, the wasm might be available on `packagist.org` and could there be installed via composer directly. For the time being, you can follow the approaches below.

### Script (Used in this example)

This demo uses a composer script to download a selected release of the compiled wasm and add it to the vendor folder.
See [composer.json](composer.json) for the script. Change the script to download a different version of the wasm or change the path if you don't want to utilize the `vendor` folder. The wasm file is not meant to be served to the client, but to be used by the server to render components, therefore it is not recommended to place it in a public folder.

### Version Management (Recommended)

Instead of manually downloading via a (composer) script, you can also use [fxpio/foxy](https://github.com/fxpio/foxy/) to manage the wasm version. This is the recommended way to manage the wasm version in a production environment, but requires Node specific package managers to be installed.

## Acknowledgements

Thank you @mariohamann for first prototyping a PHP example in https://github.com/mariohamann/enhance-ssr-wasm/tree/experiment/extism.
