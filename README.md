# Plates Assets Cache Extension

Caching and compression for Plates template assets (JavaScript and CSS).

[![Latest Version](https://img.shields.io/github/release/odan/plates-asset-cache.svg)](https://github.com/odan/plates-asset-cache/releases)
[![Build Status](https://github.com/odan/plates-asset-cache/workflows/PHP/badge.svg)](https://github.com/odan/plates-asset-cache/actions)
[![Crutinizer](https://img.shields.io/scrutinizer/g/odan/plates-asset-cache.svg)](https://scrutinizer-ci.com/g/odan/plates-asset-cache)
[![Code Coverage](https://scrutinizer-ci.com/g/odan/plates-asset-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/odan/plates-asset-cache/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/plates-asset-cache.svg)](https://packagist.org/packages/odan/plates-asset-cache/stats)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)


## Installation

```
composer require odan/plates-asset-cache
```

## Requirements

* PHP 7.0+

## Configuration

```php
use League\Plates\Engine;
use Odan\PlatesAsset\AssetEngine;
use Odan\PlatesAsset\PlatesAssetExtension;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$engine = new Engine('/path/with/html/templates');

$options = [
    // Public assets cache directory
    'path' => '/var/www/example.com/htdocs/public/assets/cache',

    // Public cache directory permissions (octal)
    // You need to prefix mode with a zero (0)
    // Use -1 to disable chmod
    'path_chmod' => 0750,

    // The public url base path
    'url_base_path' => 'assets/cache/',

    // Internal cache settings
    //
    // The main cache directory
    // Use '' (empty string) to disable the internal cache
    'cache_path' => '/var/www/example.com/htdocs/temp',

    // Used as the subdirectory of the cache_path directory,
    // where cache items will be stored
    'cache_name' => 'assets-cache',

    // The lifetime (in seconds) for cache items
    // With a value 0 causing items to be stored indefinitely
    'cache_lifetime' => 0,

    // Enable JavaScript and CSS compression
    // 1 = on, 0 = off
    'minify' => 1
];

// Register asset extension
$engine->loadExtension(new PlatesAssetExtension(new AssetEngine($engine, $options)));
```
## Usage

### The page template

Template file: `index.php`

```php
<?php /** @var League\Plates\Template\Template $this */ ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <base href="<?= $baseUrl; ?>" />
        <title>Demo</title>
        <?= $this->assets(['default.css', 'print.css'], ['inline' => true]); ?>
    </head>
    <body>
    <!-- content -->

    <!-- JavaScript assets -->
    <?= $this->assets(['mylib.js', 'page.js']); ?>
    </body>
</html>
```

### Render a template

```php
$engine = new League\Plates\Engine('/path/to/templates');

echo $engine->render('index', ['baseUrl' => '']);
```

### The result

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <base href="" />
    <title>Demo</title>
    <style></style>
<style>@media print{.noprint{display:none}.navbar{visibility:hidden;display:none}.container{width:99%}.table{table-layout:fixed;width:99%;max-width:99%}}</style></head>
<body>
<!-- content -->

<!-- JavaScript assets -->
<script src="assets/file.3dd5380c0b893eea8a14e30ce5bfa4cb9aab011b.js"></script></body>
</html>
```

#### Parameters

1. Parameter: $assets

Name | Type | Default | Required | Description
--- | --- | --- | --- | ---
files | array | [] | yes | All assets (files) to be delivered to the browser. [Plates Folders](https://platesphp.com/v3/engine/folders/) (`myalias::myfile.js`) are also supported.

2. Parameter: $options

Name | Type | Default | Required | Description
--- | --- | --- | --- | ---
inline | bool | false | no | Defines whether the browser downloads the assets inline or via URL.
minify | bool | true | no | Specifies whether JS/CSS compression is enabled or disabled.
name | string | file | no | Defines the output file name within the URL.


## Slim 4 integration

For this example we use the [PHP-DI](http://php-di.org/) package.

Add the container definition:

```php
<?php

use League\Plates\Engine;
use Odan\PlatesAsset\PlatesAssetExtension;
use Psr\Container\ContainerInterface;
use Slim\App;

// ...

return [
    // ...

    Engine::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        $viewPath = $settings['plates']['path'];

        $engine = new Engine($viewPath);

        // The public url base path
        $baseUrl = $container->get(App::class)->getBasePath();
        $engine->addData(['baseUrl' => $baseUrl]);

        $options['url_base_path'] = $basePath;

        $engine->loadExtension(new PlatesAssetExtension(new AssetEngine($engine, $options)));

        return $engine;
    },
];
```

Render the template and write content to the response stream:

```php
$response->withHeader('Content-Type', 'text/html; charset=utf-8');

$response->getBody()->write($this->engine->render($name, $viewData));

return $response;
```

## License

* MIT
