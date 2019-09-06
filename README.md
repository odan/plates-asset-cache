# Plates Assets Cache Extension

Caching and compression for Plates template assets (JavaScript and CSS).

[![Latest Version](https://img.shields.io/github/release/odan/plates-asset-cache.svg)](https://github.com/loadsys/odan/plates-asset-cache/releases)
[![Build Status](https://travis-ci.org/odan/plates-asset-cache.svg?branch=master)](https://travis-ci.org/odan/plates-asset-cache)
[![Crutinizer](https://img.shields.io/scrutinizer/g/odan/plates-asset-cache.svg)](https://scrutinizer-ci.com/g/odan/plates-asset-cache)
[![Code Coverage](https://scrutinizer-ci.com/g/odan/plates-asset-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/odan/plates-asset-cache/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/plates-asset-cache.svg)](https://packagist.org/packages/odan/plates-asset-cache/stats)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)


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
$view->loadExtension(new PlatesAssetExtension(new AssetEngine($engine, $options)));
```
## Usage

### Template

Output cached and minified CSS content:

```php
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <base href="<?= $baseurl; ?>" />
        <title>Demo</title>
        // The root path is the path defined at the instantiation (/path/with/html/templates)
        // The inline value defines, if the file should be returned as inline CSS or 
        // as <link> tag to the cache file
        <?= $this->assets(['default.css', 'print.css'], ['inline' => true]); ?>
    </head>
...
```

Output cached and minified JavaScript content:

```php
<!-- JavaScript -->
// The root path is the path defined at the instantiation (/path/with/html/templates)
// The inline value defines, if the file should be returned as inline JS or as 
// <script> tag to the cache file
<?= $this->assets(['mylib.js', 'page.js'], ['inline' => true]); ?>
```

#### Parameters

Name | Type | Default | Required | Description
--- | --- | --- | --- | ---
inline | bool | false | no | Defines whether the browser downloads the assets inline or via URL.
minify | bool | true | no | Specifies whether JS/CSS compression is enabled or disabled.
name | string | file | no | Defines the output file name within the URL.

## License

* MIT